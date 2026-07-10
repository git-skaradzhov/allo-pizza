<?php

use App\Support\Money;
use Illuminate\Support\Facades\Storage;

if (! function_exists('absolute_url')) {
    function absolute_url(?string $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount, ?int $decimals = null): string
    {
        return Money::format($amount, $decimals);
    }
}

if (! function_exists('public_media_url')) {
    function public_media_url(mixed $image): ?string
    {
        if (is_string($image)) {
            $image = trim($image);

            if (str_starts_with($image, '[') || str_starts_with($image, '{')) {
                $decoded = json_decode($image, true);
                $image = json_last_error() === JSON_ERROR_NONE ? $decoded : $image;
            }
        }

        if (is_array($image)) {
            $image = array_values($image)[0] ?? null;
        }

        if (! is_string($image) || $image === '') {
            return null;
        }

        if (str_starts_with($image, 'http')) {
            return $image;
        }

        if (str_starts_with($image, 'storage/')) {
            $image = substr($image, strlen('storage/'));
        }

        if (str_starts_with($image, '/storage/')) {
            $image = substr($image, strlen('/storage/'));
        }

        if (! Storage::disk('public')->exists($image)) {
            return null;
        }

        return route('media.public', ['path' => $image], false);
    }
}

if (! function_exists('product_image_tier_size')) {
    function product_image_tier_size(string $tier): int
    {
        return (int) config("product-images.sizes.{$tier}");
    }
}

if (! function_exists('product_image_sizes')) {
    function product_image_sizes(): array
    {
        return array_map('intval', array_values(config('product-images.sizes')));
    }
}

if (! function_exists('product_image_storage_path')) {
    function product_image_storage_path(?string $storedPath, string $tier = 'small'): ?string
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return null;
        }

        $disk = Storage::disk('public');
        $path = product_image_path($storedPath, product_image_tier_size($tier));

        if ($path !== null && $disk->exists($path)) {
            return $path;
        }

        if ($tier === 'small') {
            $legacyPath = product_image_path($storedPath, 200);

            if ($legacyPath !== null && $disk->exists($legacyPath)) {
                return $legacyPath;
            }
        }

        return $disk->exists($storedPath) ? $storedPath : null;
    }
}

if (! function_exists('product_image_path')) {
    function product_image_path(?string $storedPath, int $size = 650): ?string
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return null;
        }

        if (! in_array($size, product_image_sizes(), true)) {
            return $storedPath;
        }

        if (preg_match('/-(\d+)\.(webp|jpg|jpeg|png)$/i', $storedPath, $matches)) {
            $extension = $matches[2];

            return preg_replace('/-\d+\.(webp|jpg|jpeg|png)$/i', "-{$size}.{$extension}", $storedPath) ?? $storedPath;
        }

        return $storedPath;
    }
}

if (! function_exists('product_image_url')) {
    function product_image_url(?string $storedPath, int $size = 650): ?string
    {
        $path = product_image_path($storedPath, $size);

        if ($path === null) {
            return null;
        }

        if ($path !== $storedPath && ! Storage::disk('public')->exists($path)) {
            if ($size === product_image_tier_size('small')) {
                $legacyPath = product_image_path($storedPath, 200);

                if ($legacyPath !== $storedPath && Storage::disk('public')->exists($legacyPath)) {
                    return public_media_url($legacyPath);
                }
            }

            return public_media_url($storedPath);
        }

        $url = public_media_url($path);

        if ($url === null) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $url;
        }

        $modifiedAt = Storage::disk('public')->lastModified($path);

        return $url.'?v='.$modifiedAt;
    }
}

if (! function_exists('product_image_admin_url')) {
    function product_image_admin_url(?string $storedPath, string $tier = 'small'): ?string
    {
        $url = product_image_url($storedPath, product_image_tier_size($tier));

        if ($url !== null) {
            return url($url);
        }

        $path = product_image_storage_path($storedPath, $tier);

        if ($path === null) {
            return null;
        }

        return url(Storage::disk('public')->url($path));
    }
}
