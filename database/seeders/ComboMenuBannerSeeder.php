<?php

namespace Database\Seeders;

use App\Enums\BannerPosition;
use App\Models\Banner;
use App\Models\Page;
use App\Services\BannerImageProcessor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ComboMenuBannerSeeder extends Seeder
{
    public function run(): void
    {
        $source = database_path('seeders/assets/banners/kombo-menyu.png');

        if (! is_file($source)) {
            $this->command?->warn('Missing combo menu artwork.');

            return;
        }

        $contents = (string) file_get_contents($source);
        Storage::disk('public')->put('pages/kombo-menyu.png', $contents);
        Storage::disk('public')->put('banners/kombo-menyu.png', $contents);

        $image = app(BannerImageProcessor::class)->optimizeExisting('banners/kombo-menyu.png', 'card', true)
            ?? 'banners/kombo-menyu.png';

        Page::query()->updateOrCreate(
            ['slug' => 'kombo-menyu'],
            [
                'title' => 'Комбо меню',
                'content' => <<<'HTML'
<p>При поръчка на <strong>2 пици по 30 см</strong> получавате <strong>подарък 1 л Pepsi</strong>.</p>
<h2>Условия</h2>
<ul>
<li>Важи при поръчка на две пици от 30 см.</li>
<li>Подаръкът е 1 литър Pepsi.</li>
<li>Промоцията важи до 30.11.2026 г.</li>
<li>Не се комбинира с други оферти.</li>
</ul>
<p><a href="/menu">Поръчай от менюто</a></p>
HTML,
                'featured_image' => 'pages/kombo-menyu.png',
                'seo_title' => 'Комбо меню – подарък 1 л Pepsi | Allo! Pizza',
                'seo_description' => 'При поръчка на 2 пици по 30 см получавате подарък 1 л Pepsi. Промоцията важи до 30.11.2026 г. и не се комбинира с други оферти.',
                'is_active' => true,
            ]
        );

        Banner::query()->updateOrCreate(
            [
                'position' => BannerPosition::HomeSmallCards,
                'title' => 'Комбо меню',
            ],
            [
                'image' => $image,
                'image_only' => true,
                'button_url' => '/pages/kombo-menyu',
                'is_active' => true,
                'is_highlighted' => true,
                'sort_order' => 0,
                'starts_at' => '2026-11-18 00:00:00',
                'ends_at' => '2026-11-25 23:59:59',
            ]
        );
    }
};
