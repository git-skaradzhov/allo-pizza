<?php

namespace Tests\Feature;

use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Services\BannerImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerImageQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_optimize_images_force_rewrites_database_to_originals_and_builds_large_variants(): void
    {
        $this->putPng('banners/hero.png', 1920, 730);
        $this->putPng('banners/hero-800.webp', 800, 305);
        $this->putPng('banners/hero-mobile.png', 1200, 1200);
        $this->putPng('banners/hero-mobile-800.webp', 800, 800);

        $banner = Banner::query()->create([
            'title' => 'Hero',
            'image' => 'banners/hero-800.webp',
            'mobile_image' => 'banners/hero-mobile-800.webp',
            'position' => BannerPosition::HomeHero,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->artisan('banners:optimize-images', ['--force' => true])->assertSuccessful();

        $banner->refresh();

        $this->assertSame('banners/hero.png', $banner->image);
        $this->assertSame('banners/hero-mobile.png', $banner->mobile_image);
        Storage::disk('public')->assertExists('banners/hero-1920.webp');
        Storage::disk('public')->assertExists('banners/hero-1600.webp');
        Storage::disk('public')->assertExists('banners/hero-1200.webp');
        Storage::disk('public')->assertExists('banners/hero-mobile-1200.webp');
        Storage::disk('public')->assertExists('banners/hero-mobile-480.webp');
    }

    public function test_restore_originals_points_database_back_at_original_files(): void
    {
        $this->putPng('banners/hero.png', 1920, 730);
        $this->putPng('banners/hero-800.webp', 800, 305);

        $banner = Banner::query()->create([
            'title' => 'Hero',
            'image' => 'banners/hero-800.webp',
            'position' => BannerPosition::HomeHero,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->artisan('banners:restore-originals')->assertSuccessful();

        $this->assertSame('banners/hero.png', $banner->fresh()->image);
        Storage::disk('public')->assertMissing('banners/hero-1920.webp');
    }

    public function test_home_hero_uses_large_srcset_and_original_fallback(): void
    {
        $this->putPng('banners/hero.png', 1920, 730);

        Banner::query()->create([
            'title' => 'Седмична промоция',
            'image' => 'banners/hero.png',
            'position' => BannerPosition::HomeHero,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        app(BannerImageProcessor::class)->optimizeExisting('banners/hero.png', 'hero-desktop', true);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('1920w', false);
        $response->assertSee('1600w', false);
        $response->assertSee('1200w', false);
        $response->assertSee('800w', false);
        $response->assertSee('(min-width: 1280px) 1280px, 100vw', false);
        $response->assertSee('/storage/banners/hero.png', false);
        $response->assertSee('/storage/banners/hero-1920.webp', false);
    }

    public function test_home_hero_falls_back_to_original_when_only_800w_variant_exists(): void
    {
        $this->putPng('banners/hero.png', 2100, 800);
        $this->putPng('banners/hero-800.webp', 800, 305);

        Banner::query()->create([
            'title' => 'Седмична промоция',
            'image' => 'banners/hero-800.webp',
            'position' => BannerPosition::HomeHero,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('/storage/banners/hero.png', false);
        $response->assertDontSee('800w', false);
        $response->assertDontSee('hero-800.webp', false);
    }

    private function putPng(string $storedPath, int $width, int $height): void
    {
        $path = tempnam(sys_get_temp_dir(), 'banner-disk-');
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 220, 50, 50);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        imagepng($image, $path);
        imagedestroy($image);
        Storage::disk('public')->put($storedPath, (string) file_get_contents($path));
        @unlink($path);
    }
}
