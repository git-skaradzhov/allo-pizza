<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductImageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_store_main_accepts_jpeg_uploads(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $path = tempnam(sys_get_temp_dir(), 'product-image-');
        $image = imagecreatetruecolor(1024, 1024);
        imagejpeg($image, $path, 90);
        imagedestroy($image);
        $file = new UploadedFile($path, 'margarita.jpg', 'image/jpeg', null, true);

        $storedPath = app(ProductImageProcessor::class)->storeMain($product, $file);

        $this->assertSame('products/margarita-1024.webp', $storedPath);
    }

    public function test_store_main_scales_down_larger_square_uploads(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $file = $this->makeSquareUpload(2048, 2048);

        $storedPath = app(ProductImageProcessor::class)->storeMain($product, $file);

        $this->assertSame('products/margarita-1024.webp', $storedPath);
        Storage::disk('public')->assertExists('products/margarita-1024.webp');
    }

    public function test_validate_upload_rejects_images_smaller_than_1024(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $file = $this->makeSquareUpload(800, 800);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(ProductImageProcessor::class)->storeMain($product, $file);
    }

    public function test_store_main_accepts_non_square_images_at_least_1024(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $file = $this->makeSquareUpload(1200, 1024);

        $storedPath = app(ProductImageProcessor::class)->storeMain($product, $file);

        $this->assertSame('products/margarita-1024.webp', $storedPath);
    }

    public function test_store_main_creates_three_variant_files(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $file = $this->makeSquareUpload(1024, 1024);

        $storedPath = app(ProductImageProcessor::class)->storeMain($product, $file);

        $this->assertSame('products/margarita-1024.webp', $storedPath);
        Storage::disk('public')->assertExists('products/margarita-1024.webp');
        Storage::disk('public')->assertExists('products/margarita-650.webp');
        Storage::disk('public')->assertExists('products/margarita-350.webp');
    }

    public function test_store_gallery_uses_index_in_filename(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $file = $this->makeSquareUpload(1024, 1024);

        $storedPath = app(ProductImageProcessor::class)->storeGallery($product, 2, $file);

        $this->assertSame('products/margarita-2-1024.webp', $storedPath);
        Storage::disk('public')->assertExists('products/margarita-2-650.webp');
        Storage::disk('public')->assertExists('products/margarita-2-350.webp');
    }

    public function test_delete_variants_removes_all_sizes(): void
    {
        $product = Product::factory()->create(['slug' => 'margarita']);
        $processor = app(ProductImageProcessor::class);
        $storedPath = $processor->storeMain($product, $this->makeSquareUpload(1024, 1024));

        $processor->deleteVariants($storedPath);

        Storage::disk('public')->assertMissing('products/margarita-1024.webp');
        Storage::disk('public')->assertMissing('products/margarita-650.webp');
        Storage::disk('public')->assertMissing('products/margarita-350.webp');
    }

    public function test_product_image_url_derives_size_paths(): void
    {
        $storedPath = 'products/margarita-1024.webp';

        $this->assertSame('products/margarita-650.webp', product_image_path($storedPath, 650));
        $this->assertSame('products/margarita-350.webp', product_image_path($storedPath, 350));
    }

    public function test_rename_for_slug_change_renames_all_variants(): void
    {
        $product = Product::factory()->create(['slug' => 'old-slug']);
        $processor = app(ProductImageProcessor::class);
        $storedPath = $processor->storeMain($product, $this->makeSquareUpload(1024, 1024));
        $product->forceFill(['image' => $storedPath])->saveQuietly();

        $processor->renameForSlugChange($product, 'old-slug', 'new-slug');

        Storage::disk('public')->assertExists('products/new-slug-1024.webp');
        Storage::disk('public')->assertExists('products/new-slug-650.webp');
        Storage::disk('public')->assertExists('products/new-slug-350.webp');
        Storage::disk('public')->assertMissing('products/old-slug-1024.webp');

        $this->assertSame('products/new-slug-1024.webp', $product->image);
    }

    private function makeSquareUpload(int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'product-image-');
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 220, 50, 50);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);
        imagepng($image, $path);
        imagedestroy($image);

        return new UploadedFile($path, 'product.png', 'image/png', null, true);
    }
}
