<?php

namespace App\Filament\Concerns;

use App\Models\Product;
use App\Services\ProductImageProcessor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

trait ProcessesProductImageUpload
{
    protected function processProductImageInFormData(array $data, Product $product): array
    {
        if (! array_key_exists('image', $data) || blank($data['image'])) {
            return $data;
        }

        $image = $data['image'];

        if (is_string($image) && $this->isProcessedProductImagePath($image)) {
            return $data;
        }

        if ($image instanceof TemporaryUploadedFile) {
            $product->slug = $product->slug ?: ($data['slug'] ?? null);

            $data['image'] = app(ProductImageProcessor::class)->storeMain($product, $image);

            return $data;
        }

        return $data;
    }

    protected function isProcessedProductImagePath(string $path): bool
    {
        return (bool) preg_match('/-(?:'.implode('|', \App\Services\ProductImageProcessor::SIZES).')\.webp$/', $path);
    }
}
