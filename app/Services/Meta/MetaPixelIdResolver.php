<?php

namespace App\Services\Meta;

use App\Models\StoreSetting;

class MetaPixelIdResolver
{
    public function resolve(?StoreSetting $settings = null): ?string
    {
        $settings ??= StoreSetting::current();
        $pixelId = $settings->meta_pixel_id ?: config('meta.pixel_id');

        if (! is_string($pixelId)) {
            return null;
        }

        $pixelId = trim($pixelId);

        return $pixelId === '' ? null : $pixelId;
    }
}
