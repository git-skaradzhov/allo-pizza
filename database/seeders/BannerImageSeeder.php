<?php

namespace Database\Seeders;

use App\Enums\BannerPosition;
use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BannerImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = [
            1 => 'menu.jpg',
            2 => 'delivery.jpg',
            3 => 'promo-4-plus-1.jpg',
            4 => 'pickup.jpg',
            5 => 'delivery-zone.jpg',
        ];

        foreach ($images as $sortOrder => $filename) {
            $source = database_path("seeders/assets/banners/{$filename}");

            if (! is_file($source)) {
                continue;
            }

            $destination = "banners/{$filename}";
            Storage::disk('public')->put($destination, file_get_contents($source));

            $banner = Banner::query()->firstOrCreate(
                [
                    'position' => BannerPosition::HomeSmallCards,
                    'sort_order' => $sortOrder,
                ],
                [
                    'title' => 'Банер '.$sortOrder,
                    'is_active' => true,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonths(3),
                ]
            );

            $banner->update(['image' => $destination]);
        }
    }
}
