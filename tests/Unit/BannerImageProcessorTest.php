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

    public function test_store_creates_webp_variants_without_upscaling(): void
    {
        $file = $this->makePngUpload(1600, 600);

        $storedPath = app(BannerImageProcessor::class)->store($file, 'hero-desktop');

        $this->assertMatchesRegularExpression('/^banners\/.+-1400\.webp$/', $storedPath);
        Storage::disk('public')->assertExists($storedPath);
        $this->assertNotNull(public_media_url($storedPath));
        $this->assertStringStartsWith('/storage/banners/', public_media_url($storedPath));
    }

    public function test_picture_attributes_include_srcset_for_existing_variants(): void
    {
        $file = $this->makePngUpload(2100, 800);
        $processor = app(BannerImageProcessor::class);
        $storedPath = $processor->store($file, 'hero-desktop');

        $picture = $processor->pictureAttributes($storedPath, 'hero-desktop');

        $this->assertNotNull($picture['src']);
        $this->assertStringContainsString('2100w', (string) $picture['srcset']);
        $this->assertStringContainsString('1400w', (string) $picture['srcset']);
        $this->assertSame('image/webp', $picture['sources'][0]['type'] ?? null);
    }

    public function test_public_media_url_uses_static_storage_path(): void
    {
        Storage::disk('public')->put('banners/legacy.png', 'png');

        $this->assertSame('/storage/banners/legacy.png', public_media_url('banners/legacy.png'));
    }

    private function makePngUpload(int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'banner-image-');
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 220, 50, 50);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, 'banner.png', 'image/png', null, true);
    }
}
