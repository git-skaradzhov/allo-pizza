<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class BannerImageProcessor
{
    public const DIRECTORY = 'banners';

    public const EXTENSION = 'webp';

    public const HERO_QUALITY = 85;

    public const CARD_QUALITY = 80;

    public const MAX_WIDTH = 2560;

    public const WIDTHS = [2560, 2100, 1920, 1600, 1200, 800, 600, 480, 400];

    public const PRESETS = [
        'hero-desktop' => [1920, 1600, 1200, 800],
        'hero-mobile' => [1200, 800, 480],
        'card' => [600, 400],
        'default' => [1600, 1200, 800],
    ];

    public const SIZES = [
        'hero-desktop' => '(min-width: 1280px) 1280px, 100vw',
        'hero-mobile' => '100vw',
        'card' => '200px',
        'default' => '(min-width: 1280px) 1280px, 100vw',
    ];

    public function __construct(
        private ?ImageManager $manager = null,
    ) {
        $this->manager ??= new ImageManager(new Driver);
    }

    public function store(UploadedFile $file, string $preset = 'hero-desktop'): string
    {
        $basename = Str::slug((string) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $basename = ($basename !== '' ? $basename : 'banner').'-'.Str::lower(Str::random(8));
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'avif'], true)) {
            $extension = 'png';
        }

        $originalPath = self::DIRECTORY.'/'.$basename.'.'.$extension;
        Storage::disk('public')->put($originalPath, (string) file_get_contents($file->getRealPath()));
        $this->writeVariants($file->getRealPath(), $basename, $preset);

        return $originalPath;
    }

    public function optimizeExisting(string $storedPath, string $preset = 'default', bool $force = false): ?string
    {
        $sourceAbsolute = $this->resolveSourceAbsolutePath($storedPath);

        if ($sourceAbsolute === null) {
            return null;
        }

        $originalPath = $this->restoreOriginalPath($storedPath) ?? $storedPath;

        if (! $force && $this->hasAdequateVariants($storedPath, $preset, $sourceAbsolute)) {
            return $originalPath;
        }

        try {
            $this->writeVariants($sourceAbsolute, $this->basenameWithoutWidth($storedPath), $preset);
        } catch (\Throwable) {
            return null;
        }

        return $originalPath;
    }

    public function restoreOriginalPath(string $storedPath): ?string
    {
        $basename = $this->basenameWithoutWidth($storedPath);
        $directory = $this->directoryOf($storedPath);
        $disk = Storage::disk('public');

        foreach (['png', 'jpg', 'jpeg', 'webp', 'avif'] as $extension) {
            $candidate = $this->join($directory, $basename.'.'.$extension);

            if ($disk->exists($candidate) && ! $this->isSizedVariant($candidate)) {
                return $candidate;
            }
        }

        return $disk->exists($storedPath) ? $storedPath : null;
    }

    public function deleteVariants(?string $storedPath): void
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return;
        }

        $disk = Storage::disk('public');

        foreach ($this->discoverVariantWidths($storedPath, 'webp') as $width) {
            foreach (['webp', 'avif'] as $extension) {
                $path = $this->variantPath($storedPath, $width, $extension);

                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }
    }

    public function deleteStored(?string $storedPath): void
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return;
        }

        $this->deleteVariants($storedPath);

        $disk = Storage::disk('public');
        $original = $this->restoreOriginalPath($storedPath);

        if ($original !== null && $disk->exists($original)) {
            $disk->delete($original);
        }

        if ($disk->exists($storedPath)) {
            $disk->delete($storedPath);
        }
    }

    /**
     * @return array{src: ?string, srcset: ?string, sources: list<array{type: string, srcset: string}>, fallback: ?string, sizes: string}
     */
    public function pictureAttributes(?string $storedPath, string $preset = 'default'): array
    {
        $empty = [
            'src' => null,
            'srcset' => null,
            'sources' => [],
            'fallback' => null,
            'sizes' => self::SIZES[$preset] ?? self::SIZES['default'],
        ];

        if (! is_string($storedPath) || $storedPath === '') {
            return $empty;
        }

        $originalPath = $this->restoreOriginalPath($storedPath) ?? $storedPath;
        $fallback = public_media_url($originalPath) ?? public_media_url($storedPath);

        if ($fallback === null) {
            return $empty;
        }

        $widths = $this->discoverVariantWidths($storedPath, 'webp');

        if (! $this->variantsAreUseful($storedPath, $preset, $widths)) {
            return [
                'src' => $fallback,
                'srcset' => null,
                'sources' => [],
                'fallback' => $fallback,
                'sizes' => $empty['sizes'],
            ];
        }

        $webpSrcset = $this->srcsetFor($storedPath, $widths, 'webp');
        $avifSrcset = $this->srcsetFor($storedPath, $this->discoverVariantWidths($storedPath, 'avif'), 'avif');
        $sources = [];

        if ($avifSrcset !== null) {
            $sources[] = ['type' => 'image/avif', 'srcset' => $avifSrcset];
        }

        if ($webpSrcset !== null) {
            $sources[] = ['type' => 'image/webp', 'srcset' => $webpSrcset];
        }

        return [
            'src' => $this->preferredUrl($storedPath, $widths) ?? $fallback,
            'srcset' => $webpSrcset,
            'sources' => $sources,
            'fallback' => $fallback,
            'sizes' => $empty['sizes'],
        ];
    }

    public function variantPath(string $storedPath, int $width, string $extension = self::EXTENSION): string
    {
        return $this->join($this->directoryOf($storedPath), $this->basenameWithoutWidth($storedPath).'-'.$width.'.'.$extension);
    }

    /**
     * @return list<int>
     */
    public function presetWidths(string $preset): array
    {
        return self::PRESETS[$preset] ?? self::PRESETS['default'];
    }

    public function isSizedVariant(string $storedPath): bool
    {
        return (bool) preg_match('/-\d+\.(webp|avif)$/i', $storedPath);
    }

    public function basenameWithoutWidth(string $storedPath): string
    {
        $filename = (string) pathinfo($storedPath, PATHINFO_FILENAME);

        return (string) preg_replace('/-\d+$/', '', $filename);
    }

    /**
     * @return list<int>
     */
    public function widthsForSource(int $sourceWidth, string $preset): array
    {
        $maxOutput = min(max(1, $sourceWidth), self::MAX_WIDTH);
        $widths = array_values(array_filter(
            $this->presetWidths($preset),
            fn (int $width) => $width <= $maxOutput
        ));

        if (! in_array($maxOutput, $widths, true)) {
            $widths[] = $maxOutput;
        }

        $widths = array_values(array_unique($widths));
        rsort($widths);

        return $widths;
    }

    public function hasAdequateVariants(string $storedPath, string $preset, ?string $sourceAbsolute = null): bool
    {
        $disk = Storage::disk('public');
        $sourceAbsolute ??= $this->resolveSourceAbsolutePath($storedPath);

        if ($sourceAbsolute === null) {
            return false;
        }

        try {
            $sourceWidth = $this->manager->read($sourceAbsolute)->width();
        } catch (\Throwable) {
            return false;
        }

        $needed = $this->widthsForSource($sourceWidth, $preset);
        $largestNeeded = max($needed);

        if (! $disk->exists($this->variantPath($storedPath, $largestNeeded))) {
            return false;
        }

        $minimumHeroWidth = min(1600, $sourceWidth);

        if (str_starts_with($preset, 'hero') && $largestNeeded < $minimumHeroWidth) {
            return false;
        }

        foreach ($needed as $width) {
            if (! $disk->exists($this->variantPath($storedPath, $width))) {
                return false;
            }
        }

        return true;
    }

    public function resolveSourceAbsolutePath(string $storedPath): ?string
    {
        $disk = Storage::disk('public');
        $original = $this->restoreOriginalPath($storedPath);

        if ($original !== null && $this->isReadableImage($disk->path($original))) {
            return $disk->path($original);
        }

        foreach ($this->discoverVariantWidths($storedPath, 'webp') as $width) {
            $variant = $this->variantPath($storedPath, $width);

            if ($this->isReadableImage($disk->path($variant))) {
                return $disk->path($variant);
            }
        }

        if ($disk->exists($storedPath) && $this->isReadableImage($disk->path($storedPath))) {
            return $disk->path($storedPath);
        }

        return null;
    }

    /**
     * @return list<int>
     */
    public function discoverVariantWidths(string $storedPath, string $extension = self::EXTENSION): array
    {
        $disk = Storage::disk('public');
        $directory = $this->directoryOf($storedPath);
        $basename = $this->basenameWithoutWidth($storedPath);
        $widths = [];

        foreach ($this->allKnownWidths() as $width) {
            if ($disk->exists($this->variantPath($storedPath, $width, $extension))) {
                $widths[] = $width;
            }
        }

        foreach ($disk->files($directory !== '' ? $directory : self::DIRECTORY) as $file) {
            if (preg_match('/'.preg_quote($basename, '/').'-(\d+)\.'.$extension.'$/i', $file, $matches)) {
                $widths[] = (int) $matches[1];
            }
        }

        $widths = array_values(array_unique($widths));
        rsort($widths);

        return $widths;
    }

    private function writeVariants(string $sourcePath, string $basename, string $preset): string
    {
        $image = $this->manager->read($sourcePath);
        $sourceWidth = max(1, $image->width());
        $widths = $this->widthsForSource($sourceWidth, $preset);
        $disk = Storage::disk('public');
        $quality = $this->qualityFor($preset);
        $largestPath = self::DIRECTORY.'/'.$basename.'-'.max($widths).'.'.self::EXTENSION;

        foreach ($widths as $width) {
            $variant = $this->manager->read($sourcePath);

            if ($variant->width() > $width) {
                $variant->scale(width: $width);
            }

            $path = self::DIRECTORY.'/'.$basename.'-'.$width.'.'.self::EXTENSION;
            $disk->put($path, (string) $variant->toWebp(quality: $quality));
        }

        return $largestPath;
    }

    private function qualityFor(string $preset): int
    {
        return str_starts_with($preset, 'hero') ? self::HERO_QUALITY : self::CARD_QUALITY;
    }

    /**
     * @param  list<int>  $widths
     */
    private function variantsAreUseful(string $storedPath, string $preset, array $widths): bool
    {
        if ($widths === []) {
            return false;
        }

        $sourceWidth = $this->readImageWidth($this->resolveSourceAbsolutePath($storedPath));
        $minimumUsefulWidth = str_starts_with($preset, 'hero')
            ? min(1600, $sourceWidth ?? 1600)
            : min(max($this->presetWidths($preset)), $sourceWidth ?? max($this->presetWidths($preset)));

        return max($widths) >= $minimumUsefulWidth;
    }

    private function readImageWidth(?string $absolutePath): ?int
    {
        if ($absolutePath === null || ! is_file($absolutePath)) {
            return null;
        }

        try {
            return max(1, $this->manager->read($absolutePath)->width());
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<int>  $widths
     */
    private function srcsetFor(string $storedPath, array $widths, string $extension): ?string
    {
        $parts = [];

        foreach ($widths as $width) {
            $url = public_media_url($this->variantPath($storedPath, $width, $extension));

            if ($url !== null) {
                $parts[] = $url.' '.$width.'w';
            }
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * @param  list<int>  $widths
     */
    private function preferredUrl(string $storedPath, array $widths): ?string
    {
        foreach ($widths as $width) {
            $url = public_media_url($this->variantPath($storedPath, $width));

            if ($url !== null) {
                return $url;
            }
        }

        return public_media_url($this->restoreOriginalPath($storedPath) ?? $storedPath);
    }

    /**
     * @return list<int>
     */
    private function allKnownWidths(): array
    {
        $widths = self::WIDTHS;

        foreach (self::PRESETS as $presetWidths) {
            $widths = array_merge($widths, $presetWidths);
        }

        return array_values(array_unique($widths));
    }

    private function directoryOf(string $storedPath): string
    {
        return trim(str_replace('\\', '/', (string) dirname($storedPath)), '/.');
    }

    private function join(string $directory, string $filename): string
    {
        return $directory !== '' ? $directory.'/'.$filename : $filename;
    }

    private function isReadableImage(string $absolutePath): bool
    {
        return is_file($absolutePath) && @getimagesize($absolutePath) !== false;
    }
}
