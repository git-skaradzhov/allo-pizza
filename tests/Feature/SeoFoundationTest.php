<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleRedirects;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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
        WorkingHour::query()->updateOrCreate(
            ['day_of_week' => 1],
            ['opens_at' => '09:00:00', 'closes_at' => '21:00:00', 'is_closed' => false],
        );

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('home').'">', false);
        $response->assertSee('<meta name="robots" content="index,follow">', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee('"@type": "Organization"', false);
        $response->assertSee('"Restaurant"', false);
        $response->assertSee('"LocalBusiness"', false);
        $response->assertSee('"openingHours"', false);
        $response->assertSee('"hasMenu"', false);
        $response->assertSee('"servesCuisine"', false);
        $response->assertSee('<h1', false);
        $response->assertSee('Защо Allo! Pizza', false);
        $response->assertSee('Allo! Pizza – Поръчай пица онлайн в Русе', false);
        $this->assertMatchesRegularExpression(
            '/Защо Allo! Pizza.*Allo! Pizza – Поръчай пица онлайн в Русе/s',
            $response->getContent(),
        );
        $response->assertDontSee('sr-only">Allo! Pizza', false);
        $this->assertStringNotContainsString('Allo! Pizza | Allo! Pizza', $response->getContent());
        $this->assertStringNotContainsString('онлайн поръчка на пица | Allo! Pizza', $response->getContent());
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
        $response->assertSee('"@type": "Offer"', false);
        $response->assertSee('Описание за тестова пица.', false);
    }

    public function test_menu_page_includes_menu_item_schema(): void
    {
        $category = Category::factory()->create(['is_active' => true, 'name' => 'Пици']);
        Product::factory()->create([
            'category_id' => $category->id,
            'is_active' => true,
            'name' => 'Маргарита',
        ]);

        $response = $this->get(route('menu'));

        $response->assertOk();
        $response->assertSee('"@type": "Menu"', false);
        $response->assertSee('"@type": "MenuItem"', false);
        $response->assertSee('Маргарита', false);
        $response->assertSee('"@type": "Offer"', false);
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

    public function test_legacy_wordpress_urls_redirect_to_the_new_site(): void
    {
        $this->get('/home')->assertRedirect('/')->assertStatus(301);
        $this->get('/shop')->assertRedirect('/menu')->assertStatus(301);
        $this->get('/events/happy-solen-party')->assertRedirect('/')->assertStatus(301);
        $this->get('/get-the-sensation')->assertRedirect('/')->assertStatus(301);
        $this->get('/get-the-sensation/+359899679006')->assertRedirect('/')->assertStatus(301);
        $this->get('/tag/tasty')->assertRedirect('/menu')->assertStatus(301);
        $this->get('/best-burger-ideas')->assertRedirect('/menu')->assertStatus(301);
        $this->get('/category/seasonal')->assertRedirect('/menu')->assertStatus(301);
        $this->get('/product/chia-berry-smoothie')->assertRedirect('/category/drinks')->assertStatus(301);
        $this->get('/product/keto-meatloaf')->assertRedirect('/menu')->assertStatus(301);
        $this->get('/product/half-half-pizza')->assertRedirect('/category/pizza')->assertStatus(301);
        $this->get('/product/double-steakhous')->assertRedirect('/menu')->assertStatus(301);
        $this->get('/product-category/sides')->assertRedirect('/category/sandvici-i-pierlenki')->assertStatus(301);
        $this->get('/?page_id=5580')->assertRedirect('/')->assertStatus(301);
        $this->get('/?p=15')->assertRedirect('/')->assertStatus(301);

        $this->get('/wp-content/plugins/jetpack/jetpack_vendor/automattic/woocommerce-analytics/build')
            ->assertStatus(410);
    }

    public function test_trailing_slash_redirects_before_routing(): void
    {
        $menu = Request::create('/menu/', 'GET');
        $menuResponse = app(HandleRedirects::class)->handle($menu, fn () => response('ok', 200));

        $this->assertSame(301, $menuResponse->getStatusCode());
        $this->assertSame(url('/menu'), $menuResponse->headers->get('Location'));

        $home = Request::create('/home/', 'GET');
        $homeResponse = app(HandleRedirects::class)->handle($home, fn () => response('ok', 200));

        $this->assertSame(301, $homeResponse->getStatusCode());
        $this->assertSame(url('/'), $homeResponse->headers->get('Location'));
    }

    public function test_current_product_urls_are_not_caught_by_legacy_redirects(): void
    {
        $this->get('/product/margarita')->assertNotFound();
    }
}
