<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_sends_security_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'self'");
        $this->assertFalse($response->headers->has('Strict-Transport-Security'));
    }

    public function test_media_route_does_not_start_a_session(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('banners/hero.png', 'fake-image');

        $response = $this->get('/media/banners/hero.png');

        $response->assertRedirect('/storage/banners/hero.png');
        $this->assertNull($response->headers->get('Set-Cookie'));
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('immutable', (string) $response->headers->get('Cache-Control'));
    }

    public function test_home_page_exposes_google_favicon_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/favicon.ico', false);
        $response->assertSee('images/favicon-48.png', false);
        $response->assertSee('images/favicon-192.png', false);
        $response->assertSee('apple-touch-icon.png', false);
        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertSame([48, 48], array_slice(getimagesize(public_path('images/favicon-48.png')), 0, 2));
        $this->assertSame([192, 192], array_slice(getimagesize(public_path('images/favicon-192.png')), 0, 2));
    }

    public function test_favicon_ico_is_served_without_a_session(): void
    {
        $response = $this->get('/favicon.ico');

        $response->assertOk();
        $this->assertNull($response->headers->get('Set-Cookie'));
        $this->assertStringContainsString('image/', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=31536000', (string) $response->headers->get('Cache-Control'));
    }

    public function test_home_title_includes_city_without_duplicating_brand(): void
    {
        Page::query()->create([
            'title' => 'Начало',
            'slug' => 'home-info',
            'content' => '<p>Инфо</p>',
            'is_active' => true,
            'seo_title' => 'Allo! Pizza – онлайн поръчка на пица',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<title>Allo! Pizza – онлайн поръчка на пица в Русе</title>', false);
        $response->assertDontSee('<title>Allo! Pizza – онлайн поръчка на пица | Allo! Pizza</title>', false);
    }
}
