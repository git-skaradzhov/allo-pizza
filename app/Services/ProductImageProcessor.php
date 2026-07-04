<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductImageProcessor
{
    public const LARGE_SIZE = 1024;

    public const MEDIUM_SIZE = 650;

    public const SMALL_SIZE = 350;

    public const SIZES = [self::LARGE_SIZE, self::MEDIUM_SIZE, self::SMALL_SIZE];

    public const DIRECTORY = 'products';

    public const EXTENSION = 'webp';

    public const QUALITY = 85;

    public function __construct(
        private ?ImageManager $manager = null,
    ) {
        $this->manager ??= new ImageManager(new Driver);
    }

    public function storeMain(Product $product, UploadedFile $file): string
    {
        $this->validateUpload($file);
        $this->deleteVariants($product->image);

        return $this->store($product->slug, null, $file);
    }

    public function storeGallery(Product $product, int $index, UploadedFile $file, ?string $existingPath = null): string
    {
        $this->validateUpload($file);

        if ($existingPath) {
            $this->deleteVariants($existingPath);
        }

        return $this->store($product->slug, $index, $file);
    }

    public function validateUpload(UploadedFile $file): void
    {
        try {
            $image = $this->manager->read($file->getRealPath());
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'image' => 'Каченият файл не е валидно изображение.',
            ]);
        }

        $width = $image->width();
        $height = $image->height();

        if ($width < self::LARGE_SIZE || $height < self::LARGE_SIZE) {
            throw ValidationException::withMessages([
                'image' => "Снимката е {$width}×{$height} px. Минимум 1024×1024 px.",
            ]);
        }
    }

    public function storeFromPath(Product $product, string $sourcePath, ?int $galleryIndex = null): string
    {
        $image = $this->manager->read($sourcePath);
        $image = $this->normalizeToSquare($image);

        return $this->writeVariants($product->slug, $galleryIndex, $image);
    }

    public function deleteVariants(?string $storedPath): void
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return;
        }

        $disk = Storage::disk('public');

        foreach (self::SIZES as $size) {
            $path = $this->pathForSize($storedPath, $size);

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        if ($disk->exists($storedPath) && $storedPath !== $this->pathForSize($storedPath, self::LARGE_SIZE)) {
            $disk->delete($storedPath);
        }
    }

    public function renameForSlugChange(Product $product, string $oldSlug, string $newSlug): void
    {
        if ($oldSlug === $newSlug) {
            return;
        }

        $disk = Storage::disk('public');

        if ($product->image) {
            $newMainPath = $this->renameStoredPath($product->image, $oldSlug, $newSlug, null, $disk);

            if ($newMainPath !== $product->image) {
                $product->setAttribute('image', $newMainPath);
            }
        }

        foreach ($product->images as $galleryImage) {
            $index = $this->extractGalleryIndex($galleryImage->image, $oldSlug);

            if ($index === null) {
                continue;
            }

            $newGalleryPath = $this->renameStoredPath($galleryImage->image, $oldSlug, $newSlug, $index, $disk);

            if ($newGalleryPath !== $galleryImage->image) {
                $galleryImage->forceFill(['image' => $newGalleryPath])->saveQuietly();
            }
        }
    }

    public function nextGalleryIndex(Product $product): int
    {
        $maxIndex = 0;

        foreach ($product->images as $galleryImage) {
            $index = $this->extractGalleryIndex($galleryImage->image, $product->slug);

            if ($index !== null) {
                $maxIndex = max($maxIndex, $index);
            }
        }

        return $maxIndex + 1;
    }

    public function extractGalleryIndex(?string $storedPath, string $slug): ?int
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return null;
        }

        $basename = pathinfo($storedPath, PATHINFO_FILENAME);
        $pattern = '/^'.preg_quote($slug, '/').'-(\d+)-(?:'.implode('|', self::SIZES).')$/';

        if (preg_match($pattern, $basename, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function pathForSize(string $storedPath, int $size): string
    {
        if (preg_match('/-(\d+)\.(webp|jpg|jpeg|png)$/i', $storedPath, $matches)) {
            return preg_replace('/-\d+\.(webp|jpg|jpeg|png)$/i', "-{$size}.$1", $storedPath) ?? $storedPath;
        }

        $extension = pathinfo($storedPath, PATHINFO_EXTENSION) ?: self::EXTENSION;

        return preg_replace('/\.[^.]+$/', "-{$size}.{$extension}", $storedPath) ?? $storedPath;
    }

    public function buildBasename(string $slug, ?int $galleryIndex, int $size): string
    {
        if ($galleryIndex !== null) {
            return "{$slug}-{$galleryIndex}-{$size}";
        }

        return "{$slug}-{$size}";
    }

    private function store(string $slug, ?int $galleryIndex, UploadedFile $file): string
    {
        $image = $this->manager->read($file->getRealPath());

        return $this->writeVariants($slug, $galleryIndex, $image);
    }

    private function writeVariants(string $slug, ?int $galleryIndex, mixed $image): string
    {
        $image = $this->normalizeToSquare($image);

        if ($image->width() > self::LARGE_SIZE) {
            $image->scale(width: self::LARGE_SIZE, height: self::LARGE_SIZE);
        }

        $baseBytes = (string) $image->toWebp(quality: self::QUALITY);
        $disk = Storage::disk('public');
        $largePath = null;

        foreach (self::SIZES as $size) {
            $basename = $this->buildBasename($slug, $galleryIndex, $size);
            $path = self::DIRECTORY.'/'.$basename.'.'.self::EXTENSION;

            $variant = $this->manager->read($baseBytes);

            if ($size !== self::LARGE_SIZE) {
                $variant->scale(width: $size, height: $size);
            }

            $disk->put($path, (string) $variant->toWebp(quality: self::QUALITY));

            if ($size === self::LARGE_SIZE) {
                $largePath = $path;
            }
        }

        return $largePath ?? self::DIRECTORY.'/'.$this->buildBasename($slug, $galleryIndex, self::LARGE_SIZE).'.'.self::EXTENSION;
    }

    private function normalizeToSquare(mixed $image): mixed
    {
        $width = $image->width();
        $height = $image->height();

        if ($width === $height) {
            return $image;
        }

        $size = max($width, $height);

        return $image->cover($size, $size);
    }

    private function renameStoredPath(string $storedPath, string $oldSlug, string $newSlug, ?int $galleryIndex, $disk): string
    {
        $newPaths = [];

        foreach (self::SIZES as $size) {
            $oldPath = $this->pathForSize($storedPath, $size);
            $newBasename = $this->buildBasename($newSlug, $galleryIndex, $size);
            $newPath = self::DIRECTORY.'/'.$newBasename.'.'.self::EXTENSION;

            if ($disk->exists($oldPath)) {
                $disk->move($oldPath, $newPath);
            }

            if ($size === self::LARGE_SIZE) {
                $newPaths['large'] = $newPath;
            }
        }

        return $newPaths['large'] ?? $storedPath;
    }
}
