<?php

namespace Database\Seeders;

use App\Enums\BannerPosition;
use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::query()
            ->whereIn('position', [
                BannerPosition::HomeHero,
                BannerPosition::HomeSmallCards,
                BannerPosition::LunchSection,
            ])
            ->update(['is_active' => false]);

        Banner::query()
            ->where('title', 'Работно време')
            ->where('position', BannerPosition::HomeSmallCards)
            ->update(['is_active' => false]);

        $banners = [
            [
                'title' => 'Топла пица до вратата',
                'subtitle' => 'Пици, пърленки, салати и напитки с бърза доставка.',
                'button_text' => 'Виж менюто',
                'button_url' => '/menu',
                'position' => BannerPosition::HomeHero,
                'sort_order' => 1,
            ],
            [
                'title' => 'Основно меню',
                'subtitle' => 'Пици, пърленки, салати и напитки.',
                'button_text' => 'Виж менюто',
                'button_url' => '/menu',
                'position' => BannerPosition::HomeSmallCards,
                'sort_order' => 1,
            ],
            [
                'title' => 'Безплатна доставка',
                'subtitle' => 'при поръчки на стойност над 30 €',
                'button_text' => 'Поръчай сега',
                'button_url' => '/menu',
                'position' => BannerPosition::HomeSmallCards,
                'sort_order' => 2,
            ],
            [
                'title' => '4 + 1 безплатно',
                'subtitle' => 'при поръчка на 4 пици с размер 30 см получавате още 1 пица подарък',
                'button_text' => 'Поръчай',
                'button_url' => '/menu',
                'position' => BannerPosition::HomeSmallCards,
                'sort_order' => 3,
            ],
            [
                'title' => '15% отстъпка',
                'subtitle' => 'за взимане от място при поръчка, направена минимум 2 часа предварително',
                'button_text' => 'Поръчай',
                'button_url' => '/menu',
                'position' => BannerPosition::HomeSmallCards,
                'sort_order' => 4,
            ],
            [
                'title' => 'Доставка в района',
                'subtitle' => '2 € в района · 3 € извън района. Районът между ул. „Николаевска“, бул. „Цар Освободител“, ЖП гарата и бул. „Ген. Скобelev“',
                'button_text' => 'Виж зоните',
                'button_url' => '/pages/dostavka',
                'position' => BannerPosition::HomeSmallCards,
                'sort_order' => 5,
            ],
            [
                'title' => 'Обедно меню',
                'subtitle' => 'Пица, салата или пърленка от 12:00 до 16:00.',
                'button_text' => 'Виж обедното меню',
                'button_url' => '/obedno-menyu',
                'position' => BannerPosition::LunchSection,
                'sort_order' => 1,
            ],
        ];

        foreach ($banners as $banner) {
            Banner::query()->updateOrCreate(
                [
                    'title' => $banner['title'],
                    'position' => $banner['position'],
                ],
                array_merge($banner, [
                    'is_active' => true,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addMonths(3),
                ])
            );
        }
    }
}
