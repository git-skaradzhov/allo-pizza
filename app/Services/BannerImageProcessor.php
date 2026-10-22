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

    public const QUALITY = 78;

    public const WIDTHS = [2100, 1400, 800, 480];

    public const PRESETS = [
        'hero-desktop' => [2100, 1400, 800],
        'hero-mobile' => [800, 480],
        'card' => [600, 400],
        'default' => [1400, 800],
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

        return $this->writeVariants($file->getRealPath(), $basename, $preset);
    }

    public function optimizeExisting(string $storedPath, string $preset = 'default'): ?string
    {
        if ($this->hasVariants($storedPath, $preset)) {
            return $this->canonicalPath($storedPath, $preset);
        }

        $disk = Storage::disk('public');
        $absolute = $disk->path($storedPath);

        if (! is_file($absolute)) {
            return null;
        }

        $basename = pathinfo($storedPath, PATHINFO_FILENAME);
        $basename = (string) preg_replace('/-(\d+)$/', '', $basename);

        if ($basename === '') {
            $basename = 'banner-'.Str::lower(Str::random(8));
        }

        try {
            return $this->writeVariants($absolute, $basename, $preset);
        } catch (\Throwable) {
            return null;
        }
    }

    public function deleteVariants(?string $storedPath): void
    {
        if (! is_string($storedPath) || $storedPath === '') {
            return;
        }

        $disk = Storage::disk('public');

        foreach ($this->allKnownWidths() as $width) {
            foreach (['webp', 'avif'] as $extension) {
                $path = $this->variantPath($storedPath, $width, $extension);

                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }

        if ($disk->exists($storedPath) && ! preg_match('/-\d+\.webp$/i', $storedPath)) {
            $disk->delete($storedPath);
        }
    }

    /**
     * @return array{src: ?string, srcset: ?string, sources: list<array{type: string, srcset: string}>}
     */
    public function pictureAttributes(?string $storedPath, string $preset = 'default'): array
    {
        $empty = ['src' => null, 'srcset' => null, 'sources' => []];

        if (! is_string($storedPath) || $storedPath === '') {
            return $empty;
        }

        $fallback = public_media_url($storedPath);

        if ($fallback === null) {
            return $empty;
        }

        $widths = $this->presetWidths($preset);
        $webpSrcset = $this->srcsetFor($storedPath, $widths, 'webp');
        $avifSrcset = $this->srcsetFor($storedPath, $widths, 'avif');
        $sources = [];

        if ($avifSrcset !== null) {
            $sources[] = ['type' => 'image/avif', 'srcset' => $avifSrcset];
        }

        if ($webpSrcset !== null) {
            $sources[] = ['type' => 'image/webp', 'srcset' => $webpSrcset];
        }

        $preferred = $this->preferredUrl($storedPath, $widths);

        return [
            'src' => $preferred ?? $fallback,
            'srcset' => $webpSrcset,
            'sources' => $sources,
        ];
    }

    public function variantPath(string $storedPath, int $width, string $extension = self::EXTENSION): string
    {
        $directory = trim(str_replace('\\', '/', (string) dirname($storedPath)), '/.');
        $filename = (string) preg_replace('/-\d+$/', '', (string) pathinfo($storedPath, PATHINFO_FILENAME));
        $prefix = $directory !== '' ? $directory.'/' : '';

        return "{$prefix}{$filename}-{$width}.{$extension}";
    }

    /**
     * @return list<int>
     */
    public function presetWidths(string $preset): array
    {
        return self::PRESETS[$preset] ?? self::PRESETS['default'];
    }

    /**
     * @param  list<int>  $widths
     */
    public function hasVariants(string $storedPath, string $preset): bool
    {
        $disk = Storage::disk('public');

        foreach ($this->presetWidths($preset) as $width) {
            if (! $disk->exists($this->variantPath($storedPath, $width))) {
                return false;
            }
        }

        return true;
    }

    public function canonicalPath(string $storedPath, string $preset): string
    {
        $largest = max($this->presetWidths($preset));

        return $this->variantPath($storedPath, $largest);
    }

    private function writeVariants(string $sourcePath, string $basename, string $preset): string
    {
        $image = $this->manager->read($sourcePath);
        $sourceWidth = max(1, $image->width());
        $widths = $this->widthsForSource($sourceWidth, $preset);
        $disk = Storage::disk('public');
        $largestPath = self::DIRECTORY.'/'.$basename.'-'.max($widths).'.'.self::EXTENSION;

        foreach ($widths as $width) {
            $variant = $this->manager->read($sourcePath);

            if ($variant->width() > $width) {
                $variant->scale(width: $width);
            }

            $path = self::DIRECTORY.'/'.$basename.'-'.$width.'.'.self::EXTENSION;
            $disk->put($path, (string) $variant->toWebp(quality: self::QUALITY));
        }

        return $largestPath;
    }

    /**
     * @return list<int>
     */
    private function widthsForSource(int $sourceWidth, string $preset): array
    {
        $wanted = $this->presetWidths($preset);
        $widths = array_values(array_filter($wanted, fn (int $width) => $width <= $sourceWidth));

        if ($widths === []) {
            $widths[] = min($wanted);
        }

        $widths = array_values(array_unique($widths));
        rsort($widths);

        return $widths;
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

        return public_media_url($storedPath);
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
}
