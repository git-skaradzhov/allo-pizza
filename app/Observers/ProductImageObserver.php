<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\ProductImageProcessor;

class ProductImageObserver
{
    public function __construct(
        private ProductImageProcessor $processor,
    ) {}

    public function updating(ProductImage $productImage): void
    {
        if ($productImage->isDirty('image') && $productImage->getOriginal('image')) {
            $this->processor->deleteVariants($productImage->getOriginal('image'));
        }
    }

    public function deleting(ProductImage $productImage): void
    {
        $this->processor->deleteVariants($productImage->image);
    }
}
