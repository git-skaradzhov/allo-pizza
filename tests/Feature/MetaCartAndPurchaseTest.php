<?php

namespace Tests\Feature;

use App\Enums\CartItemType;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\LunchMenu;
use App\Models\LunchMenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\WorkingHour;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesStoreData;
use Tests\Concerns\InteractsWithMetaTracking;
use Tests\TestCase;

class MetaCartAndPurchaseTest extends TestCase
{
    use CreatesStoreData;
    use InteractsWithMetaTracking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->createOpenStore();
        $this->withPixelId();
        $this->enableMetaCapi();
        $this->fakeMetaCapiSuccessful();
        Mail::fake();
    }

    public function test_add_to_cart_is_not_sent_before_successful_add(): void
    {
        $this->grantMarketingConsent()
            ->post('/cart/add', [
                'product_id' => 999999,
                'product_variant_id' => 999999,
                'quantity' => 1,
            ])
            ->assertSessionHasErrors();

        Http::assertNothingSent();
    }

    public function test_add_to_cart_is_sent_only_after_success_and_includes_extras(): void
    {
        $product = $this->createProductWithVariant(10);
        $variant = $product->variants->first();
        $extra = Ingredient::query()->create([
            'name' => 'Пеперони',
            'price' => 1.50,
            'is_removable' => false,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->grantMarketingConsent()
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'extras' => [$extra->id => 1],
            ])
            ->assertRedirect(route('cart'));

        $unitPrice = (float) $variant->price + 1.50;
        $sent = $this->sentCapiEventsNamed('AddToCart');

        $this->assertCount(1, $sent);
        $event = $sent[0]['data'][0];
        $this->assertSame(['product_'.$product->id], $event['custom_data']['content_ids']);
        $this->assertSame('product_'.$product->id, $event['custom_data']['contents'][0]['id']);
        $this->assertSame(2, $event['custom_data']['contents'][0]['quantity']);
        $this->assertEqualsWithDelta($unitPrice, $event['custom_data']['contents'][0]['item_price'], 0.001);
        $this->assertEqualsWithDelta($unitPrice * 2, $event['custom_data']['value'], 0.001);
        $this->assertSame('EUR', $event['custom_data']['currency']);

        $cartPage = $this->get(route('cart'));
        $flash = $this->jsonFromScript($cartPage->getContent(), 'meta-flash-events');
        $browserEvent = $this->firstPageEvent($flash, 'AddToCart');

        $this->assertNotNull($browserEvent);
        $this->assertSame($event['event_id'], $browserEvent['event_id']);
        $this->assertEqualsWithDelta($unitPrice * 2, $browserEvent['payload']['value'], 0.001);
    }

    public function test_different_sizes_use_the_same_content_id(): void
    {
        $product = $this->createProductWithVariant(10);
        $small = $product->variants->first();
        $large = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Голяма',
            'size_label' => '40 см',
            'price' => 18.00,
            'is_active' => true,
            'sort_order' => 9,
        ]);

        $this->grantMarketingConsent();

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $small->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart'));

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $large->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart'));

        $events = $this->sentCapiEventsNamed('AddToCart');
        $this->assertCount(2, $events);
        $this->assertSame(['product_'.$product->id], $events[0]['data'][0]['custom_data']['content_ids']);
        $this->assertSame(['product_'.$product->id], $events[1]['data'][0]['custom_data']['content_ids']);
    }

    public function test_initiate_checkout_excludes_unconfirmed_delivery(): void
    {
        $product = $this->createProductWithVariant(10);
        $variant = $product->variants->first();
        $customer = Customer::factory()->create();

        $this->actingAs($customer->user);
        $this->grantMarketingConsent()
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ])
            ->assertRedirect(route('cart'));

        $this->fakeMetaCapiSuccessful();

        $checkout = $this->get(route('checkout'));
        $this->assertFalse(
            $checkout->isRedirect(),
            'Checkout redirected to '.$checkout->headers->get('Location').' with error: '.session('error')
        );
        $checkout->assertOk();

        $events = $this->jsonFromScript($checkout->getContent(), 'meta-page-events');
        $initiate = $this->firstPageEvent($events, 'InitiateCheckout');

        $this->assertNotNull($initiate);
        $this->assertEqualsWithDelta((float) $variant->price, $initiate['payload']['value'], 0.001);
        $this->assertSame('EUR', $initiate['payload']['currency']);
        $this->assertArrayNotHasKey('delivery_price', $initiate['payload']);

        $this->postJson(route('meta.events.store'), ['token' => $initiate['token']])->assertOk();

        $sent = $this->sentCapiEventsNamed('InitiateCheckout');
        $this->assertCount(1, $sent);
        $this->assertEqualsWithDelta((float) $variant->price, $sent[0]['data'][0]['custom_data']['value'], 0.001);
        $this->assertSame($initiate['event_id'], $sent[0]['data'][0]['event_id']);
    }

    public function test_purchase_is_sent_after_commit_with_order_total_and_matching_event_id(): void
    {
        $product = $this->createProductWithVariant(10);
        $variant = $product->variants->first();
        $customer = Customer::factory()->create([
            'email' => 'ivan@example.com',
            'phone' => '0888123456',
        ]);

        $this->actingAs($customer->user);
        $this->grantMarketingConsent()
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        $response = $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'customer_email' => 'ivan@example.com',
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Пример 1',
            'delivery_lat' => 43.8407468,
            'delivery_lng' => 25.9536970,
        ]);

        $response->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('purchase_order_'.$order->id, $order->meta_purchase_event_id);
        $this->assertNotNull($order->meta_purchase_sent_at);
        $this->assertSame('product_'.$product->id, $order->items()->first()->meta_content_id);

        $sent = $this->sentCapiEventsNamed('Purchase');
        $this->assertCount(1, $sent);
        $event = $sent[0]['data'][0];

        $this->assertSame('purchase_order_'.$order->id, $event['event_id']);
        $this->assertEqualsWithDelta((float) $order->total, $event['custom_data']['value'], 0.001);
        $this->assertGreaterThan((float) $order->subtotal - (float) $order->discount, (float) $order->total);
        $this->assertEqualsWithDelta((float) $order->delivery_price, (float) $order->total - ((float) $order->subtotal - (float) $order->discount), 0.001);
        $this->assertSame('EUR', $event['custom_data']['currency']);
        $this->assertSame($order->order_number, $event['custom_data']['order_id']);
        $this->assertSame(['product_'.$product->id], $event['custom_data']['content_ids']);
        $this->assertSame(hash('sha256', 'ivan@example.com'), $event['user_data']['em']);
        $this->assertSame(hash('sha256', '359888123456'), $event['user_data']['ph']);
        $this->assertSame(hash('sha256', (string) $customer->id), $event['user_data']['external_id']);
        $this->assertArrayNotHasKey('email', $event['user_data']);
        $this->assertStringNotContainsString('ivan@example.com', json_encode($event['user_data']));
        $this->assertStringNotContainsString('0888123456', json_encode($event['user_data']));

        $thankYou = $this->get($response->headers->get('Location'));
        $flash = $this->jsonFromScript($thankYou->getContent(), 'meta-flash-events');
        $browserPurchase = $this->firstPageEvent($flash, 'Purchase');

        $this->assertNotNull($browserPurchase);
        $this->assertSame($order->meta_purchase_event_id, $browserPurchase['event_id']);
        $this->assertEqualsWithDelta((float) $order->total, $browserPurchase['payload']['value'], 0.001);

        $refresh = $this->get(route('account.orders.show', $order));
        $refreshFlash = $this->jsonFromScript($refresh->getContent(), 'meta-flash-events') ?? [];
        $this->assertNull($this->firstPageEvent($refreshFlash, 'Purchase'));
    }

    public function test_failed_transaction_does_not_send_purchase(): void
    {
        $product = $this->createProductWithVariant(10);
        $variant = $product->variants->first();
        $customer = Customer::factory()->create();

        $this->actingAs($customer->user);
        $this->grantMarketingConsent()
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        Http::fake([
            'https://graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
        ]);

        Order::creating(function () {
            throw new \RuntimeException('simulated db failure');
        });

        $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'customer_email' => 'ivan@example.com',
            'delivery_type' => 'pickup',
        ])->assertStatus(500);

        $this->assertDatabaseCount('orders', 0);
        $this->assertCount(0, $this->sentCapiEventsNamed('Purchase'));
    }

    public function test_capi_failure_does_not_fail_the_order(): void
    {
        Http::swap(new Factory);
        Http::fake(fn () => Http::response(['error' => ['message' => 'temporarily unavailable']], 500));

        $product = $this->createProductWithVariant(10);
        $variant = $product->variants->first();
        $customer = Customer::factory()->create();

        $this->actingAs($customer->user);
        $this->grantMarketingConsent()
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'customer_email' => 'ivan@example.com',
            'delivery_type' => 'pickup',
        ])->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertNull($order->meta_purchase_sent_at);
        $this->assertGreaterThan(0, $order->meta_purchase_attempts);
        $this->assertSame('purchase_order_'.$order->id, $order->meta_purchase_event_id);
        $this->assertNotNull($order->meta_purchase_last_error);
        $this->assertStringNotContainsString($this->metaCapiToken, (string) $order->meta_purchase_last_error);
    }

    public function test_purchase_is_not_sent_without_marketing_consent(): void
    {
        $product = $this->createProductWithVariant(10);
        $variant = $product->variants->first();
        $customer = Customer::factory()->create();

        $this->actingAs($customer->user);
        $this->denyMarketingConsent()
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'delivery_type' => 'pickup',
        ])->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertNull($order->meta_purchase_event_id);
        $this->assertNull($order->meta_purchase_sent_at);
        $this->assertCount(0, $this->sentCapiEventsNamed('Purchase'));
        $this->assertCount(0, $this->sentCapiEventsNamed('AddToCart'));
    }

    public function test_retry_command_reuses_the_same_purchase_event_id(): void
    {
        $product = $this->createProductWithVariant(10);
        $order = Order::factory()->create([
            'customer_email' => 'retry@example.com',
            'customer_phone' => '0888000111',
            'subtotal' => 12.50,
            'discount' => 0,
            'delivery_price' => 2.00,
            'total' => 14.50,
            'meta_purchase_event_id' => null,
            'meta_purchase_sent_at' => null,
            'meta_purchase_attempts' => 1,
        ]);
        $order->update(['meta_purchase_event_id' => 'purchase_order_'.$order->id]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'item_type' => CartItemType::Product,
            'product_id' => $product->id,
            'meta_content_id' => 'product_'.$product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 12.50,
            'total_price' => 12.50,
        ]);

        $this->artisan('meta:retry-purchases', ['--limit' => 50])->assertSuccessful();

        $order->refresh();
        $this->assertNotNull($order->meta_purchase_sent_at);
        $this->assertSame('purchase_order_'.$order->id, $order->meta_purchase_event_id);

        $sent = $this->sentCapiEventsNamed('Purchase');
        $this->assertCount(1, $sent);
        $this->assertSame('purchase_order_'.$order->id, $sent[0]['data'][0]['event_id']);
        $this->assertEqualsWithDelta(14.50, $sent[0]['data'][0]['custom_data']['value'], 0.001);
        $this->assertEqualsWithDelta(2.00, (float) $order->delivery_price, 0.001);
    }

    public function test_retry_skips_orders_without_consent_at_creation(): void
    {
        Order::factory()->create([
            'meta_purchase_event_id' => null,
            'meta_purchase_sent_at' => null,
        ]);

        $this->artisan('meta:retry-purchases', ['--limit' => 50])->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_lunch_purchase_uses_lunch_content_id(): void
    {
        Carbon::setTestNow(Carbon::parse('next monday 13:00'));
        WorkingHour::query()->updateOrCreate(
            ['day_of_week' => now()->dayOfWeekIso],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:59', 'is_closed' => false],
        );

        $customer = Customer::factory()->create();
        $this->actingAs($customer->user);

        $item = LunchMenuItem::query()->create([
            'section' => 'Супи',
            'name' => 'Таратор',
            'description' => '300 гр.',
            'price' => 3.13,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $menu = LunchMenu::query()->create([
            'title' => 'Обедно меню',
            'start_time' => '12:00:00',
            'end_time' => '16:00:00',
            'days_of_week' => [1, 2, 3, 4, 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $menu->items()->sync([$item->id]);

        $this->grantMarketingConsent()
            ->post(route('lunch.items.add', $item), ['quantity' => 2])
            ->assertRedirect(route('cart'));

        $this->post(route('checkout.store'), [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'delivery_type' => 'pickup',
        ])->assertRedirect();

        $order = Order::query()->first();
        $this->assertSame('lunch_'.$item->id, $order->items()->first()->meta_content_id);

        $sent = $this->sentCapiEventsNamed('Purchase');
        $this->assertSame(['lunch_'.$item->id], $sent[0]['data'][0]['custom_data']['content_ids']);
    }
}
