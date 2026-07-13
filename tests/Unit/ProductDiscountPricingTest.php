<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDiscountPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_without_variants_shows_discount_summary(): void
    {
        $category = Category::factory()->create();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Пърленка',
            'slug' => 'parlenka',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 5.90,
            'old_price' => 7.50,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($product->isDiscounted());

        $summary = $product->priceSummary();

        $this->assertSame('от 5.90 €', $summary['current_label']);
        $this->assertSame('от 7.50 €', $summary['old_label']);
        $this->assertSame(1.60, $summary['savings']);
        $this->assertNull($summary['savings_max']);
    }

    public function test_product_with_variants_scales_old_price_by_discount_ratio(): void
    {
        $category = Category::factory()->create();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Промо пица',
            'slug' => 'promo-pizza',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 6.90,
            'old_price' => 8.90,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $small = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '30 см',
            'size_label' => '30 см',
            'price' => 6.90,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $large = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '45 см',
            'size_label' => '45 см',
            'price' => 12.50,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $product->load('variants');

        $this->assertTrue($product->isDiscounted());
        $this->assertSame(8.90, $product->variantOldPrice($small));
        $this->assertSame(16.12, $product->variantOldPrice($large));

        $summary = $product->priceSummary();

        $this->assertSame('6.90 € – 12.50 €', $summary['current_label']);
        $this->assertSame('8.90 € – 16.12 €', $summary['old_label']);
        $this->assertSame(2.0, $summary['savings']);
        $this->assertSame(3.62, $summary['savings_max']);
    }

    public function test_product_without_old_price_is_not_discounted(): void
    {
        $category = Category::factory()->create();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Напитка',
            'slug' => 'napitka',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 5.90,
            'old_price' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertFalse($product->isDiscounted());
        $this->assertNull($product->priceSummary()['old_label']);
    }

    public function test_is_promo_syncs_automatically_when_product_has_discount(): void
    {
        $category = Category::factory()->create();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Промо продукт',
            'slug' => 'promo-produkt',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 5.90,
            'old_price' => 7.50,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($product->is_promo);

        $product->update(['old_price' => null]);

        $this->assertFalse($product->fresh()->is_promo);
    }

    public function test_is_promo_syncs_when_variant_price_changes(): void
    {
        $category = Category::factory()->create();

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Пица',
            'slug' => 'pitsa',
            'short_description' => 'Тест',
            'description' => 'Тест',
            'base_price' => 6.90,
            'old_price' => 8.90,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => '30 см',
            'size_label' => '30 см',
            'price' => 6.90,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertTrue($product->fresh()->is_promo);

        $variant->update(['price' => 9.50]);

        $this->assertFalse($product->fresh()->is_promo);
    }
}
