<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Models\WorkingHour;
use App\Support\DeliveryZone;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function makeProduct(): Product
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'base_price' => 10,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Стандартна',
            'size_label' => '30 см',
            'price' => 12.50,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        StoreSetting::query()->create([
            'store_name' => 'Allo! Pizza',
            'store_email' => 'admin@allopizza.test',
            'store_lat' => 43.8407475,
            'store_lng' => 25.9549665,
            'delivery_radius_km' => 50,
            'delivery_zone_polygon' => DeliveryZone::defaultPolygon(),
            'delivery_price' => 3,
            'delivery_inside_price' => 2,
            'delivery_outside_price' => 3,
            'free_delivery_over' => 30,
            'minimum_order_amount' => 0,
            'is_store_open' => true,
        ]);

        WorkingHour::query()->create([
            'day_of_week' => now()->dayOfWeekIso,
            'opens_at' => '00:00:00',
            'closes_at' => '23:59:59',
            'is_closed' => false,
        ]);

        return $product->fresh('variants');
    }

    public function test_can_add_product_to_cart(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();

        $response = $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart'));

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);
    }

    public function test_can_place_order_and_email_is_queued(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $variant = $product->variants->first();

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'customer_email' => 'ivan@example.com',
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Пример 1',
            'delivery_lat' => 43.8407468,
            'delivery_lng' => 25.9536970,
            'payment_method' => 'cash_on_delivery',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
        ]);

        $order = Order::query()->first();
        $expectedTotal = (float) $variant->price + 2.0;
        $this->assertEqualsWithDelta($expectedTotal, (float) $order->total, 0.001);
        $this->assertEqualsWithDelta(2.0, (float) $order->delivery_price, 0.001);

        Mail::assertSent(OrderConfirmation::class);
        Mail::assertSent(NewOrderAdminNotification::class);
    }

    public function test_order_outside_zone_charges_outside_delivery_price(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $variant = $product->variants->first();

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout', [
            'customer_name' => 'Петър Петров',
            'customer_phone' => '0888999888',
            'customer_email' => 'petar@example.com',
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Далечна 99',
            'delivery_lat' => 43.9000,
            'delivery_lng' => 26.1000,
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::query()->first();

        $this->assertEqualsWithDelta(3.0, (float) $order->delivery_price, 0.001);
        $this->assertEqualsWithDelta((float) $variant->price + 3.0, (float) $order->total, 0.001);
    }

    public function test_order_includes_extras_in_database(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $variant = $product->variants->first();
        $extra = Ingredient::query()->create([
            'name' => 'Пеперони',
            'price' => 1.00,
            'is_removable' => false,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'extras' => [$extra->id => 1],
        ]);

        $this->post('/checkout', [
            'customer_name' => 'Тест Клиент',
            'customer_phone' => '0888123456',
            'customer_email' => 'test@example.com',
            'delivery_type' => 'pickup',
            'payment_method' => 'pay_at_store',
        ])->assertRedirect();

        $orderItem = Order::query()->first()->items()->first();

        $this->assertDatabaseHas('order_item_options', [
            'order_item_id' => $orderItem->id,
            'name' => 'Пеперони',
            'option_type' => 'extra_added',
        ]);
    }

    public function test_extra_price_uses_variant_multiplier(): void
    {
        $product = $this->makeProduct();
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Голяма',
            'size_label' => '45 см',
            'price' => 18.00,
            'extra_price_multiplier' => 1.50,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $extra = Ingredient::query()->create([
            'name' => 'Моцарела',
            'price' => 1.00,
            'is_removable' => false,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'extras' => [$extra->id => 1],
        ]);

        $cartItem = \App\Models\CartItem::query()->first();

        $this->assertEqualsWithDelta(19.50, (float) $cartItem->unit_price, 0.001);
    }

    public function test_extra_quantity_multiplies_unit_price(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();
        $extra = Ingredient::query()->create([
            'name' => 'Пеперони',
            'price' => 1.00,
            'is_removable' => false,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'extras' => [$extra->id => 2],
        ]);

        $cartItem = \App\Models\CartItem::query()->first();

        $this->assertEqualsWithDelta((float) $variant->price + 2.00, (float) $cartItem->unit_price, 0.001);
        $this->assertEquals(2, $cartItem->options[0]['quantity'] ?? 0);
    }

    public function test_drink_products_ignore_extras(): void
    {
        $category = Category::factory()->create([
            'slug' => 'drinks',
            'allows_extras' => false,
        ]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'base_price' => 1.30,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Стандартен',
            'size_label' => '0,5 л',
            'price' => 1.30,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $extra = Ingredient::query()->create([
            'name' => 'Пеперони',
            'price' => 1.00,
            'is_removable' => false,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'extras' => [$extra->id => 2],
            'note' => 'Без лед',
        ]);

        $cartItem = \App\Models\CartItem::query()->first();

        $this->assertEqualsWithDelta((float) $variant->price, (float) $cartItem->unit_price, 0.001);
        $this->assertEmpty($cartItem->options);
        $this->assertNull($cartItem->note);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertDontSee('Добави съставки')
            ->assertDontSee('Бележка');
    }

    public function test_removed_recipe_ingredient_ignores_matching_extra(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();
        $extra = Ingredient::query()->create([
            'name' => 'Пеперони',
            'price' => 1.00,
            'is_removable' => true,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'extras' => [$extra->id => 2],
            'removed' => [$extra->id],
        ]);

        $cartItem = \App\Models\CartItem::query()->first();

        $this->assertEqualsWithDelta((float) $variant->price, (float) $cartItem->unit_price, 0.001);
        $this->assertCount(1, $cartItem->options);
        $this->assertSame('ingredient_removed', $cartItem->options[0]['type']);
    }

    public function test_checkout_rejects_subtotal_below_minimum(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();

        StoreSetting::query()->first()->update(['minimum_order_amount' => 50]);

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'delivery_type' => 'pickup',
        ])->assertSessionHasErrors('subtotal');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_redirects_when_below_minimum_on_index(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();

        StoreSetting::query()->first()->update(['minimum_order_amount' => 50]);

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->get(route('checkout'))
            ->assertRedirect(route('cart'))
            ->assertSessionHas('error', 'Минималната стойност на поръчката не е достигната.');
    }

    public function test_delivery_requires_coordinates(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Пример 1',
        ])->assertSessionHasErrors('delivery_lat');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_delivery_rejects_address_outside_delivery_radius(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants->first();

        StoreSetting::query()->first()->update(['delivery_radius_km' => 5]);

        $customer = \App\Models\Customer::factory()->create();
        $this->actingAs($customer->user);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->post('/checkout', [
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '0888123456',
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Далечна 99',
            'delivery_lat' => 43.9000,
            'delivery_lng' => 26.1000,
        ])->assertSessionHasErrors('delivery_address');

        $this->assertDatabaseCount('orders', 0);
    }
}
