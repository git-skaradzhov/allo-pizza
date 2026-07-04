<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_api_returns_active_categories(): void
    {
        Category::factory()->create(['name' => 'Пица', 'slug' => 'pizza', 'is_active' => true]);
        Category::factory()->create(['name' => 'Скрита', 'slug' => 'hidden', 'is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'pizza');
    }

    public function test_products_api_returns_active_products(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);
        Product::factory()->create(['category_id' => $category->id, 'is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_inactive_product_page_returns_not_found(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'slug' => 'skrit-produkt',
            'is_active' => false,
        ]);

        $this->get(route('product.show', $product->slug))->assertNotFound();
    }

    public function test_inactive_product_cannot_be_added_to_cart(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);
        $variant = \App\Models\ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => 'Стандартна',
            'price' => 10,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post('/cart/add', [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertNotFound();
    }
}
