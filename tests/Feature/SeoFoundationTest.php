<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_is_available_and_references_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap:');
        $response->assertSee('/sitemap.xml');
        $response->assertSee('Disallow: /admin');
    }

    public function test_sitemap_xml_lists_public_urls(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        $page = Page::query()->create([
            'title' => 'За нас',
            'slug' => 'za-nas',
            'content' => '<p>Тест</p>',
            'is_active' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('menu'), false);
        $response->assertSee(route('category.show', $category->slug), false);
        $response->assertSee(route('product.show', $product->slug), false);
        $response->assertSee(route('pages.show', $page->slug), false);
    }

    public function test_home_page_has_canonical_meta_and_json_ld(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('home').'">', false);
        $response->assertSee('<meta name="robots" content="index,follow">', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "Organization"', false);
        $response->assertSee('<h1', false);
    }

    public function test_product_page_has_product_schema_and_open_graph(): void
    {
        $category = Category::factory()->create(['is_active' => true]);
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'seo_title' => 'Тестова пица',
            'seo_description' => 'Описание за тестова пица.',
        ]);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();
        $response->assertSee('<meta property="og:type" content="product">', false);
        $response->assertSee('"@type": "Product"', false);
        $response->assertSee('Тестова пица | Allo! Pizza', false);
        $response->assertSee('Описание за тестова пица.', false);
    }

    public function test_private_pages_are_noindex(): void
    {
        $this->get('/cart')->assertSee('<meta name="robots" content="noindex,nofollow">', false);
        $this->get('/user/login')->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_redirect_middleware_applies_permanent_redirect(): void
    {
        Redirect::query()->create([
            'from_path' => '/stara-stranica',
            'to_path' => '/menu',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $response = $this->get('/stara-stranica');

        $response->assertRedirect(route('menu'));
        $response->assertStatus(301);
    }
}
