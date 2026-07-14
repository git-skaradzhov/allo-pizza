<?php

namespace Tests\Feature;

use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    use RefreshDatabase;
    use CreatesStoreData;

    protected function makePizza30(float $price = 12.50, ?string $name = null): Product
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'pizza'],
            [
                'name' => 'Пици',
                'description' => 'Тест',
                'image' => null,
                'is_active' => true,
                'allows_extras' => true,
                'sort_order' => 1,
            ]
        );

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => $name ?? 'Тестова пица',
            'slug' => \Illuminate\Support\Str::slug($name ?? 'test-pizza-'.uniqid()),
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => $price,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '30 см',
            'size_label' => '30 см',
            'diameter' => 30,
            'price' => $price,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return $product->fresh('variants', 'category');
    }

    protected function apiOrderPayload(Product $product, int $quantity, array $extra = []): array
    {
        $variant = $product->variants->first();

        return array_merge([
            'customer_name' => 'Мария',
            'customer_email' => 'maria@example.com',
            'customer_phone' => '0899111222',
            'delivery_type' => 'pickup',
            'payment_method' => 'pay_at_store',
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                ],
            ],
        ], $extra);
    }

    protected function configureBundlePromotion(bool $enabled, ?array $categoryIds = null): void
    {
        StoreSetting::current()->update([
            'bundle_promotion_enabled' => $enabled,
            'bundle_promotion_category_ids' => $categoryIds,
        ]);
    }

    public function test_can_create_order_via_api(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $product = $this->createProductWithVariant(15);
        $variant = $product->variants->first();

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Мария',
            'customer_email' => 'maria@example.com',
            'customer_phone' => '0899111222',
            'delivery_type' => 'pickup',
            'payment_method' => 'pay_at_store',
            'items' => [
                ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.customer_name', 'Мария');

        $this->assertEqualsWithDelta((float) $variant->price * 2, (float) $response->json('data.subtotal'), 0.01);
        $this->assertDatabaseHas('orders', ['customer_email' => 'maria@example.com']);
        Mail::assertSent(OrderConfirmation::class);
        Mail::assertSent(NewOrderAdminNotification::class);
    }

    public function test_api_order_requires_items(): void
    {
        $this->createOpenStore();

        $this->postJson('/api/orders', [
            'customer_name' => 'Мария',
            'customer_phone' => '0899111222',
            'delivery_type' => 'pickup',
            'payment_method' => 'pay_at_store',
            'items' => [],
        ])->assertStatus(422);
    }

    public function test_api_order_applies_pizza_bundle_discount(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $product = $this->makePizza30(10.00);

        $response = $this->postJson('/api/orders', $this->apiOrderPayload($product, 5));

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 50)
            ->assertJsonPath('data.discount', 10)
            ->assertJsonPath('data.total', 40);

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'maria@example.com',
            'discount' => 10,
            'promo_code' => null,
            'total' => 40,
        ]);
    }

    public function test_api_order_ignores_promo_code_when_bundle_is_active(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $product = $this->makePizza30(10.00);

        PromoCode::query()->create([
            'code' => 'PIZZA20',
            'discount_percent' => 20,
            'is_active' => true,
            'used_count' => 0,
        ]);

        $response = $this->postJson('/api/orders', $this->apiOrderPayload($product, 5, [
            'promo_code' => 'PIZZA20',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.discount', 10)
            ->assertJsonPath('data.total', 40);

        $this->assertDatabaseHas('orders', [
            'discount' => 10,
            'promo_code' => null,
        ]);
        $this->assertSame(0, PromoCode::query()->first()->used_count);
    }

    public function test_api_order_applies_promo_code_when_bundle_is_not_active(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $product = $this->makePizza30(10.00);

        PromoCode::query()->create([
            'code' => 'PIZZA20',
            'discount_percent' => 20,
            'is_active' => true,
            'used_count' => 0,
        ]);

        $response = $this->postJson('/api/orders', $this->apiOrderPayload($product, 3, [
            'promo_code' => 'PIZZA20',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 30)
            ->assertJsonPath('data.discount', 6)
            ->assertJsonPath('data.total', 24);

        $this->assertDatabaseHas('orders', [
            'discount' => 6,
            'promo_code' => 'PIZZA20',
            'total' => 24,
        ]);
        $this->assertSame(1, PromoCode::query()->first()->used_count);
    }

    public function test_api_order_skips_bundle_discount_when_promotion_is_disabled(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $this->configureBundlePromotion(false);
        $product = $this->makePizza30(10.00);

        $response = $this->postJson('/api/orders', $this->apiOrderPayload($product, 5));

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', 50)
            ->assertJsonPath('data.discount', 0)
            ->assertJsonPath('data.total', 50);
    }

    public function test_api_order_rejects_subtotal_below_minimum(): void
    {
        $this->createOpenStore();
        StoreSetting::current()->update(['minimum_order_amount' => 20]);
        $product = $this->createProductWithVariant(5);

        $this->postJson('/api/orders', $this->apiOrderPayload($product, 1))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['subtotal']);
    }

    public function test_api_delivery_requires_coordinates(): void
    {
        $this->createOpenStore();
        $product = $this->createProductWithVariant(15);

        $this->postJson('/api/orders', $this->apiOrderPayload($product, 2, [
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Пример 1',
            'payment_method' => 'cash_on_delivery',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_lat']);
    }

    public function test_api_delivery_rejects_address_outside_delivery_radius(): void
    {
        $this->createOpenStore();
        StoreSetting::current()->update(['delivery_radius_km' => 5]);
        $product = $this->createProductWithVariant(15);

        $this->postJson('/api/orders', $this->apiOrderPayload($product, 2, [
            'delivery_type' => 'delivery',
            'delivery_address' => 'ул. Далечна 99',
            'delivery_lat' => 43.9000,
            'delivery_lng' => 26.1000,
            'payment_method' => 'cash_on_delivery',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['delivery_address']);
    }

    public function test_api_order_rejects_inactive_product(): void
    {
        $this->createOpenStore();
        $product = $this->createProductWithVariant(15);
        $product->update(['is_active' => false]);

        $this->postJson('/api/orders', $this->apiOrderPayload($product, 1))
            ->assertNotFound();
    }

    public function test_api_order_rejects_inactive_variant(): void
    {
        $this->createOpenStore();
        $product = $this->createProductWithVariant(15);
        $product->variants->first()->update(['is_active' => false]);

        $this->postJson('/api/orders', $this->apiOrderPayload($product, 1))
            ->assertNotFound();
    }
}
