<?php

namespace App\Console\Commands;

use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Services\BannerImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OptimizeBannerImages extends Command
{
    protected $signature = 'banners:optimize-images
                            {--force : Regenerate variants even if they already exist}
                            {--restore-only : Point database columns at original files without rewriting variants}';

    protected $description = 'Convert existing banner uploads to responsive WebP variants and keep originals in the database';

    public function handle(BannerImageProcessor $processor): int
    {
        $disk = Storage::disk('public');
        $optimized = 0;
        $restored = 0;
        $force = (bool) $this->option('force');
        $restoreOnly = (bool) $this->option('restore-only');

        Banner::query()
            ->orderBy('id')
            ->each(function (Banner $banner) use ($processor, $disk, $force, $restoreOnly, &$optimized, &$restored) {
                foreach ($this->fieldsFor($banner) as $field => $preset) {
                    $current = $banner->{$field};

                    if (! is_string($current) || $current === '') {
                        continue;
                    }

                    $original = $processor->restoreOriginalPath($current);

                    if ($original === null && ! $disk->exists($current)) {
                        $this->warn("Missing {$field} for banner #{$banner->id}: {$current}");

                        continue;
                    }

                    if ($restoreOnly) {
                        if ($original !== null && $original !== $current) {
                            $banner->forceFill([$field => $original])->saveQuietly();
                            $restored++;
                            $this->line("Restored {$field} for banner #{$banner->id}: {$original}");
                        }

                        continue;
                    }

                    $newPath = $processor->optimizeExisting($current, $preset, $force);

                    if ($newPath === null) {
                        $this->warn("Could not optimize {$field} for banner #{$banner->id}: {$current}");

                        continue;
                    }

                    if ($newPath !== $current) {
                        $banner->forceFill([$field => $newPath])->saveQuietly();
                        $restored++;
                    }

                    $optimized++;
                    $this->line("Optimized {$field} for banner #{$banner->id}");
                }
            });

        if ($restoreOnly) {
            $this->info("Done. Restored {$restored} banner image path(s).");
        } else {
            $this->info("Done. Optimized {$optimized} banner image(s). Restored {$restored} path(s) to originals.");
        }

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
