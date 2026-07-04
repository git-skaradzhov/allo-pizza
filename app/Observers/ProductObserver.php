<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ProductImageProcessor;

class ProductObserver
{
    public function __construct(
        private ProductImageProcessor $processor,
    ) {}

    public function updating(Product $product): void
    {
        if ($product->isDirty('slug') && $product->getOriginal('slug')) {
            $this->processor->renameForSlugChange(
                $product,
                $product->getOriginal('slug'),
                $product->slug,
            );
        }

        if ($product->isDirty('image') && $product->getOriginal('image')) {
            $this->processor->deleteVariants($product->getOriginal('image'));
        }
    }

    public function deleting(Product $product): void
    {
        $this->processor->deleteVariants($product->image);

        foreach ($product->images as $galleryImage) {
            $this->processor->deleteVariants($galleryImage->image);
        }
    }
}
