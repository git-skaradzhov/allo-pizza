<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class CheckoutThanksTest extends TestCase
{
    use CreatesStoreData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
    }

    public function test_guest_is_redirected_to_thank_you_page_after_checkout(): void
    {
        $this->createOpenStore();
        $product = $this->createProductWithVariant(12.50);
        $variant = $product->variants->first();

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart'));

        $response = $this->post('/checkout', [
            'customer_name' => 'Гост Клиент',
            'customer_phone' => '0888123456',
            'delivery_type' => 'pickup',
        ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);

        $response->assertRedirect(route('checkout.thanks', $order));

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Благодарим')
            ->assertSee($order->order_number)
            ->assertSee('Гост Клиент')
            ->assertSee($product->name)
            ->assertSee('Принтирай / Запази като PDF')
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_logged_in_customer_is_also_redirected_to_thank_you_page(): void
    {
        $this->createOpenStore();
        $product = $this->createProductWithVariant(12.50);
        $variant = $product->variants->first();
        $customer = Customer::factory()->create();

        $this->actingAs($customer->user)
            ->post('/cart/add', [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ]);

        $response = $this->post('/checkout', [
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone ?? '0888123456',
            'delivery_type' => 'pickup',
        ]);

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('checkout.thanks', $order));
    }

    public function test_second_checkout_submit_returns_to_the_thank_you_page(): void
    {
        $this->createOpenStore();
        $product = $this->createProductWithVariant(12.50);
        $variant = $product->variants->first();

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout', [
            'customer_name' => 'Гост Клиент',
            'customer_phone' => '0888123456',
            'delivery_type' => 'pickup',
        ])->assertRedirect();

        $order = Order::query()->first();
        $this->assertNotNull($order);

        $this->post('/checkout', [
            'customer_name' => 'Гост Клиент',
            'customer_phone' => '0888123456',
            'delivery_type' => 'pickup',
        ])->assertRedirect(route('checkout.thanks', $order));
    }

    public function test_thank_you_page_is_not_public(): void
    {
        $order = Order::factory()->create();

        $this->get(route('checkout.thanks', $order))->assertNotFound();
    }

    public function test_customer_can_open_own_thank_you_page(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);

        $this->actingAs($customer->user)
            ->get(route('checkout.thanks', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }
}
