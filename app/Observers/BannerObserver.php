<?php

namespace App\Observers;

use App\Models\Banner;
use App\Services\BannerImageProcessor;

class BannerObserver
{
    public function __construct(
        private BannerImageProcessor $processor,
    ) {}

    public function updating(Banner $banner): void
    {
        foreach (['image', 'mobile_image'] as $field) {
            if ($banner->isDirty($field) && $banner->getOriginal($field)) {
                $this->processor->deleteStored((string) $banner->getOriginal($field));
            }
        }
    }

    public function deleting(Banner $banner): void
    {
        $this->processor->deleteStored($banner->image);
        $this->processor->deleteStored($banner->mobile_image);
    }
}
