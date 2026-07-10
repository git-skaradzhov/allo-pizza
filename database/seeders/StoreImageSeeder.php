<?php

namespace Database\Seeders;

use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class StoreImageSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            'exterior-hero.png',
            'exterior-contact.png',
            'exterior-side.png',
            'interior-about.png',
            'interior-wide.png',
            'interior-counter.png',
        ];

        foreach ($assets as $filename) {
            $source = database_path("seeders/assets/store/{$filename}");

            if (! is_file($source)) {
                continue;
            }

            Storage::disk('public')->put("store/{$filename}", file_get_contents($source));
        }

        $pageImages = [
            'za-nas' => 'store/interior-about.png',
        ];

        foreach ($pageImages as $slug => $image) {
            Page::query()
                ->where('slug', $slug)
                ->update(['featured_image' => $image]);
        }

        Page::query()
            ->whereIn('slug', ['kontakti', 'dostavka'])
            ->update(['featured_image' => null]);

        Banner::query()
            ->where('position', BannerPosition::HomeHero)
            ->update(['image' => null]);
    }
}
