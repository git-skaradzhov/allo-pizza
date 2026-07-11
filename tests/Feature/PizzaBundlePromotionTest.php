<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PromoCode;
use App\Models\StoreSetting;
use App\Services\PizzaBundlePromotionService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class PizzaBundlePromotionTest extends TestCase
{
    use CreatesStoreData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    protected function actingAsCustomer(): Customer
    {
        $customer = Customer::factory()->create();
        $this->actingAs($customer->user);

        return $customer;
    }

    protected function pizzaCategory(): Category
    {
        return Category::query()->firstOrCreate(
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
    }

    protected function makePizza30(float $price = 12.50, ?string $name = null): Product
    {
        $category = $this->pizzaCategory();

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

    protected function variant30(Product $product): ProductVariant
    {
        return $product->variants()->where('diameter', 30)->firstOrFail();
    }

    protected function addPizzaToCart(Product $product, int $quantity = 1, array $extras = []): void
    {
        $payload = [
            'product_id' => $product->id,
            'product_variant_id' => $this->variant30($product)->id,
            'quantity' => $quantity,
        ];

        if ($extras !== []) {
            $payload['extras'] = $extras;
        }

        $this->post('/cart/add', $payload)->assertRedirect(route('cart'));
    }

    protected function configureBundlePromotion(bool $enabled, ?array $categoryIds = null): void
    {
        StoreSetting::current()->update([
            'bundle_promotion_enabled' => $enabled,
            'bundle_promotion_category_ids' => $categoryIds,
        ]);
    }

    protected function drinksCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'napitki'],
            [
                'name' => 'Напитки',
                'description' => 'Тест',
                'image' => null,
                'is_active' => true,
                'allows_extras' => false,
                'sort_order' => 2,
            ]
        );
    }

    protected function makeDrink(float $price = 3.00, ?string $name = null): Product
    {
        $category = $this->drinksCategory();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => $name ?? 'Кола',
            'slug' => \Illuminate\Support\Str::slug($name ?? 'kola-'.uniqid()),
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => $price,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '0.5 л',
            'size_label' => '0.5 л',
            'diameter' => null,
            'price' => $price,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return $product->fresh('variants', 'category');
    }

    protected function addProductToCart(Product $product, int $quantity = 1): void
    {
        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => $quantity,
        ])->assertRedirect(route('cart'));
    }

    public function test_four_pizzas_show_progress_towards_free_pizza(): void
    {
        $this->createOpenStore();
        $this->actingAsCustomer();
        $product = $this->makePizza30();

        $this->addPizzaToCart($product, 4);

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(4, $bundle->eligibleQuantity);
        $this->assertSame(1, $bundle->remainingUntilFree);

        $this->get('/cart')
            ->assertOk()
            ->assertSee('добавете още', false)
            ->assertSee('безплатен', false);
    }

    public function test_five_pizzas_apply_cheapest_free_discount(): void
    {
        $this->createOpenStore();
        $this->actingAsCustomer();
        $cheap = $this->makePizza30(10.00, 'Евтина');
        $expensive = $this->makePizza30(15.00, 'Скъпа');

        $this->post('/cart/add', [
            'product_id' => $expensive->id,
            'product_variant_id' => $this->variant30($expensive)->id,
            'quantity' => 3,
        ])->assertRedirect(route('cart'));

        $this->post('/cart/add', [
            'product_id' => $cheap->id,
            'product_variant_id' => $this->variant30($cheap)->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart'));

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(5, $bundle->eligibleQuantity);
        $this->assertSame(1, $bundle->freeCount);
        $this->assertEqualsWithDelta(10.0, $bundle->discount, 0.01);

        $this->get('/cart')
            ->assertOk()
            ->assertSee('безплатен', false);
    }

    public function test_bundle_promotion_disabled_applies_no_discount(): void
    {
        $this->createOpenStore();
        $this->configureBundlePromotion(false);
        $this->actingAsCustomer();
        $product = $this->makePizza30(10.00);

        $this->addPizzaToCart($product, 5);

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(0, $bundle->eligibleQuantity);
        $this->assertSame(0.0, $bundle->discount);

        $this->get('/cart')
            ->assertOk()
            ->assertDontSee('добавете още', false)
            ->assertDontSee('4+1', false);
    }

    public function test_selected_non_pizza_categories_count_all_variants(): void
    {
        $this->createOpenStore();
        $pizzaCategory = $this->pizzaCategory();
        $drinksCategory = $this->drinksCategory();
        $this->configureBundlePromotion(true, [$pizzaCategory->id, $drinksCategory->id]);
        $this->actingAsCustomer();

        $pizza30 = $this->makePizza30(12.00);
        $drink = $this->makeDrink(3.00);

        $this->addPizzaToCart($pizza30, 2);
        $this->addProductToCart($drink, 3);

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(5, $bundle->eligibleQuantity);
        $this->assertSame(1, $bundle->freeCount);
        $this->assertEqualsWithDelta(3.0, $bundle->discount, 0.01);
    }

    public function test_forty_five_cm_pizza_not_counted_when_drinks_category_also_selected(): void
    {
        $this->createOpenStore();
        $pizzaCategory = $this->pizzaCategory();
        $drinksCategory = $this->drinksCategory();
        $this->configureBundlePromotion(true, [$pizzaCategory->id, $drinksCategory->id]);
        $this->actingAsCustomer();

        $drink = $this->makeDrink(3.00);
        $category = $this->pizzaCategory();
        $largePizza = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Голяма пица',
            'slug' => 'golema-pizza-mixed',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 20,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::query()->create([
            'product_id' => $largePizza->id,
            'name' => '45 см',
            'size_label' => '45 см',
            'diameter' => 45,
            'price' => 20,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $largePizza = $largePizza->fresh('variants');

        $this->addProductToCart($drink, 3);
        $this->addProductToCart($largePizza, 2);

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(3, $bundle->eligibleQuantity);
        $this->assertSame(0, $bundle->freeCount);
    }

    public function test_ten_pizzas_apply_two_free_discounts(): void
    {
        $this->createOpenStore();
        $this->actingAsCustomer();
        $product = $this->makePizza30(12.00);

        $this->addPizzaToCart($product, 10);

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(2, $bundle->freeCount);
        $this->assertEqualsWithDelta(24.0, $bundle->discount, 0.01);
    }

    public function test_forty_five_cm_pizzas_are_not_eligible(): void
    {
        $this->createOpenStore();
        $this->actingAsCustomer();
        $category = $this->pizzaCategory();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Голяма пица',
            'slug' => 'golema-pizza-test',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 20,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '45 см',
            'size_label' => '45 см',
            'diameter' => 45,
            'price' => 20,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $product = $product->fresh('variants');

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $product->variants->first()->id,
            'quantity' => 5,
        ])->assertRedirect(route('cart'));

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(0, $bundle->eligibleQuantity);
        $this->assertSame(0.0, $bundle->discount);
    }

    public function test_free_discount_includes_extras_in_unit_price(): void
    {
        $this->createOpenStore();
        $this->actingAsCustomer();
        $product = $this->makePizza30(10.00);
        $variant = $this->variant30($product);
        $extra = Ingredient::query()->create([
            'name' => 'Моцарела (добавка)',
            'price' => 2.00,
            'is_removable' => false,
            'is_extra' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->addPizzaToCart($product, 4);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'extras' => [$extra->id => 1],
        ])->assertRedirect(route('cart'));

        $cart = app(\App\Services\CartService::class)->getCart()->load(['items.product.category', 'items.variant']);
        $bundle = app(PizzaBundlePromotionService::class)->evaluate($cart);

        $this->assertSame(1, $bundle->freeCount);
        $this->assertEqualsWithDelta(10.0, $bundle->discount, 0.01);
    }

    public function test_promo_code_is_ignored_when_bundle_is_active(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $this->actingAsCustomer();

        $product = $this->makePizza30(10.00);

        PromoCode::query()->create([
            'code' => 'PIZZA20',
            'discount_percent' => 20,
            'is_active' => true,
            'used_count' => 0,
        ]);

        $this->addPizzaToCart($product, 5);

        $this->post('/cart/promo', ['code' => 'PIZZA20'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->post('/checkout', [
                'customer_name' => 'Тест',
                'customer_phone' => '0888000000',
                'delivery_type' => 'pickup',
                'payment_method' => 'pay_at_store',
            ])->assertRedirect();

        $order = Order::query()->first();

        $this->assertEqualsWithDelta(10.0, (float) $order->discount, 0.01);
        $this->assertNull($order->promo_code);
        $this->assertEqualsWithDelta(40.0, (float) $order->total, 0.01);
    }

    public function test_promo_code_works_when_bundle_is_not_active(): void
    {
        Mail::fake();
        $this->createOpenStore();
        $this->actingAsCustomer();

        $product = $this->makePizza30(10.00);

        PromoCode::query()->create([
            'code' => 'PIZZA20',
            'discount_percent' => 20,
            'is_active' => true,
            'used_count' => 0,
        ]);

        $this->addPizzaToCart($product, 3);

        $this->withSession(['promo_code' => 'PIZZA20'])
            ->post('/checkout', [
                'customer_name' => 'Тест',
                'customer_phone' => '0888000000',
                'delivery_type' => 'pickup',
                'payment_method' => 'pay_at_store',
            ])->assertRedirect();

        $order = Order::query()->first();

        $this->assertEqualsWithDelta(6.0, (float) $order->discount, 0.01);
        $this->assertSame('PIZZA20', $order->promo_code);
    }
}
