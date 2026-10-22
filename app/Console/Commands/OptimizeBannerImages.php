<?php

namespace App\Console\Commands;

use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Services\BannerImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeBannerImages extends Command
{
    protected $signature = 'banners:optimize-images';

    protected $description = 'Convert existing banner uploads to responsive WebP variants';

    public function handle(BannerImageProcessor $processor): int
    {
        $disk = Storage::disk('public');
        $optimized = 0;

        Banner::query()
            ->orderBy('id')
            ->each(function (Banner $banner) use ($processor, $disk, &$optimized) {
                foreach ($this->fieldsFor($banner) as $field => $preset) {
                    $current = $banner->{$field};

                    if (! is_string($current) || $current === '') {
                        continue;
                    }

                    if (! $disk->exists($current)) {
                        $this->warn("Missing {$field} for banner #{$banner->id}: {$current}");

                        continue;
                    }

                    $newPath = $processor->optimizeExisting($current, $preset);

                    if ($newPath === null) {
                        $this->warn("Could not optimize {$field} for banner #{$banner->id}: {$current}");

                        continue;
                    }

                    if ($newPath !== $current) {
                        $banner->forceFill([$field => $newPath])->saveQuietly();
                    }

                    $optimized++;
                    $this->line("Optimized {$field} for banner #{$banner->id}");
                }
            });

        $this->info("Done. Optimized {$optimized} banner image(s).");

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    private function fieldsFor(Banner $banner): array
    {
        if ($banner->position === BannerPosition::HomeHero) {
            return [
                'image' => 'hero-desktop',
                'mobile_image' => 'hero-mobile',
            ];
        }

        return [
            'image' => 'card',
        ];
    }
}
