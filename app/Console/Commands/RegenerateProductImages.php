<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegenerateProductImages extends Command
{
    protected $signature = 'products:regenerate-images';

    protected $description = 'Regenerate product image variants (1024/650/350) from legacy uploads';

    public function handle(ProductImageProcessor $processor): int
    {
        $disk = Storage::disk('public');
        $regenerated = 0;

        Product::query()
            ->whereNotNull('image')
            ->orderBy('id')
            ->each(function (Product $product) use ($processor, $disk, &$regenerated) {
                if ($this->isVariantPath($product->image) && $this->hasCurrentVariants($product->image, $processor, $disk)) {
                    return;
                }

                $sourcePath = $this->resolveSourcePath($product->image, $product->slug, $processor, $disk);

                if ($sourcePath === null) {
                    $this->warn("Missing source for product {$product->slug}: {$product->image}");

                    return;
                }

                $oldPath = $product->image;
                $this->deleteLegacySmallVariant($oldPath, $processor, $disk);
                $newPath = $processor->storeFromPath($product, $sourcePath);
                $product->forceFill(['image' => $newPath])->saveQuietly();

                if ($oldPath !== $newPath && $disk->exists($oldPath)) {
                    $disk->delete($oldPath);
                }

                $regenerated++;
                $this->line("Regenerated main image for {$product->slug}");
            });

        Product::query()
            ->with('images')
            ->orderBy('id')
            ->each(function (Product $product) use ($processor, $disk, &$regenerated) {
                foreach ($product->images as $index => $galleryImage) {
                    if ($this->isVariantPath($galleryImage->image) && $this->hasCurrentVariants($galleryImage->image, $processor, $disk)) {
                        continue;
                    }

                    $sourcePath = $this->resolveSourcePath($galleryImage->image, $product->slug, $processor, $disk);

                    if ($sourcePath === null) {
                        $this->warn("Missing gallery source for {$product->slug}: {$galleryImage->image}");

                        continue;
                    }

                    $galleryIndex = $index + 1;
                    $oldPath = $galleryImage->image;
                    $this->deleteLegacySmallVariant($oldPath, $processor, $disk);
                    $newPath = $processor->storeFromPath($product, $sourcePath, $galleryIndex);
                    $galleryImage->forceFill(['image' => $newPath])->saveQuietly();

                    if ($oldPath !== $newPath && $disk->exists($oldPath)) {
                        $disk->delete($oldPath);
                    }

                    $regenerated++;
                    $this->line("Regenerated gallery image #{$galleryIndex} for {$product->slug}");
                }
            });

        $this->info("Done. Regenerated {$regenerated} image set(s).");

        return self::SUCCESS;
    }

    private function hasCurrentVariants(string $path, ProductImageProcessor $processor, $disk): bool
    {
        foreach (ProductImageProcessor::SIZES as $size) {
            if (! $disk->exists($processor->pathForSize($path, $size))) {
                return false;
            }
        }

        return true;
    }

    private function deleteLegacySmallVariant(string $path, ProductImageProcessor $processor, $disk): void
    {
        $legacySmallPath = preg_replace('/-350\.webp$/', '-200.webp', $processor->pathForSize($path, ProductImageProcessor::SMALL_SIZE));

        if (is_string($legacySmallPath) && $disk->exists($legacySmallPath)) {
            $disk->delete($legacySmallPath);
        }
    }

    private function isVariantPath(string $path): bool
    {
        return (bool) preg_match('/-(?:'.implode('|', ProductImageProcessor::SIZES).'|200)\.webp$/', $path);
    }

    private function resolveSourcePath(string $storedPath, string $slug, ProductImageProcessor $processor, $disk): ?string
    {
        if ($this->isVariantPath($storedPath)) {
            $largePath = $processor->pathForSize($storedPath, ProductImageProcessor::LARGE_SIZE);
            $diskPath = $disk->path($largePath);

            if (is_file($diskPath)) {
                return $diskPath;
            }
        }

        $diskPath = Storage::disk('public')->path($storedPath);

        if (is_file($diskPath)) {
            return $diskPath;
        }

        $basename = basename($storedPath);
        $seedFromBasename = database_path("seeders/assets/products/{$basename}");

        if (is_file($seedFromBasename)) {
            return $seedFromBasename;
        }

        foreach (['png', 'jpg', 'jpeg', 'webp'] as $extension) {
            $seedFromSlug = database_path("seeders/assets/products/{$slug}.{$extension}");

            if (is_file($seedFromSlug)) {
                return $seedFromSlug;
            }
        }

        return null;
    }
}
