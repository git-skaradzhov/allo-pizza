<?php

namespace Tests\Unit;

use App\Services\BannerImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerImageProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_store_keeps_the_original_and_writes_webp_variants_without_upscaling(): void
    {
        $file = $this->makePngUpload(1600, 600);
        $processor = app(BannerImageProcessor::class);

        $storedPath = $processor->store($file, 'hero-desktop');

        $this->assertMatchesRegularExpression('/^banners\/.+\.png$/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
        Storage::disk('public')->assertExists($processor->variantPath($storedPath, 1600));
        Storage::disk('public')->assertExists($processor->variantPath($storedPath, 1200));
        Storage::disk('public')->assertExists($processor->variantPath($storedPath, 800));
        Storage::disk('public')->assertMissing($processor->variantPath($storedPath, 1920));
        $this->assertNotNull(public_media_url($storedPath));
        $this->assertStringStartsWith('/storage/banners/', public_media_url($storedPath));
    }

    public function test_picture_attributes_include_large_srcset_sizes_and_original_fallback(): void
    {
        $file = $this->makePngUpload(2100, 800);
        $processor = app(BannerImageProcessor::class);
        $storedPath = $processor->store($file, 'hero-desktop');

        $picture = $processor->pictureAttributes($storedPath, 'hero-desktop');

        $this->assertNotNull($picture['src']);
        $this->assertSame($storedPath, $processor->restoreOriginalPath($storedPath));
        $this->assertSame('/storage/'.$storedPath, $picture['fallback']);
        $this->assertSame(BannerImageProcessor::SIZES['hero-desktop'], $picture['sizes']);
        $this->assertStringContainsString('2100w', (string) $picture['srcset']);
        $this->assertStringContainsString('1920w', (string) $picture['srcset']);
        $this->assertStringContainsString('1600w', (string) $picture['srcset']);
        $this->assertStringContainsString('1200w', (string) $picture['srcset']);
        $this->assertStringContainsString('800w', (string) $picture['srcset']);
        $this->assertStringNotContainsString('1400w', (string) $picture['srcset']);
        $this->assertSame('image/webp', $picture['sources'][0]['type'] ?? null);
    }

    public function test_picture_attributes_skip_undersized_srcset_and_use_the_original(): void
    {
        $this->putPng('banners/hero.png', 2100, 800);
        $this->putPng('banners/hero-800.webp', 800, 305);
        $processor = app(BannerImageProcessor::class);

        $picture = $processor->pictureAttributes('banners/hero-800.webp', 'hero-desktop');

        $this->assertSame('/storage/banners/hero.png', $picture['src']);
        $this->assertSame('/storage/banners/hero.png', $picture['fallback']);
        $this->assertNull($picture['srcset']);
        $this->assertSame([], $picture['sources']);
    }

    public function test_optimize_existing_restores_the_original_path_and_writes_large_variants(): void
    {
        $this->putPng('banners/hero.png', 1920, 730);
        $this->putPng('banners/hero-800.webp', 800, 305);
        $processor = app(BannerImageProcessor::class);

        $restored = $processor->optimizeExisting('banners/hero-800.webp', 'hero-desktop', true);

        $this->assertSame('banners/hero.png', $restored);
        Storage::disk('public')->assertExists('banners/hero-1920.webp');
        Storage::disk('public')->assertExists('banners/hero-1600.webp');
        Storage::disk('public')->assertExists('banners/hero-1200.webp');
        Storage::disk('public')->assertExists('banners/hero-800.webp');
        Storage::disk('public')->assertExists('banners/hero.png');
    }

    public function test_delete_variants_keeps_the_original(): void
    {
        $file = $this->makePngUpload(1600, 600);
        $processor = app(BannerImageProcessor::class);
        $storedPath = $processor->store($file, 'hero-desktop');

        $processor->deleteVariants($storedPath);

        Storage::disk('public')->assertExists($storedPath);
        Storage::disk('public')->assertMissing($processor->variantPath($storedPath, 1600));
        Storage::disk('public')->assertMissing($processor->variantPath($storedPath, 800));
    }

    public function test_delete_stored_removes_original_and_variants(): void
    {
        $file = $this->makePngUpload(1600, 600);
        $processor = app(BannerImageProcessor::class);
        $storedPath = $processor->store($file, 'hero-desktop');

        $processor->deleteStored($storedPath);

        Storage::disk('public')->assertMissing($storedPath);
        Storage::disk('public')->assertMissing($processor->variantPath($storedPath, 1600));
    }

    public function test_public_media_url_uses_static_storage_path(): void
    {
        Storage::disk('public')->put('banners/legacy.png', 'png');

        $this->assertSame('/storage/banners/legacy.png', public_media_url('banners/legacy.png'));
    }

    private function makePngUpload(int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'banner-image-');
        $this->writePng($path, $width, $height);

        return new UploadedFile($path, 'banner.png', 'image/png', null, true);
    }

    private function putPng(string $storedPath, int $width, int $height): void
    {
        $path = tempnam(sys_get_temp_dir(), 'banner-disk-');
        $this->writePng($path, $width, $height);
        Storage::disk('public')->put($storedPath, (string) file_get_contents($path));
        @unlink($path);
    }

    private function writePng(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 220, 50, 50);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        imagepng($image, $path);
        imagedestroy($image);
    }
}
