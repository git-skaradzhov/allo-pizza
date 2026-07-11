<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::query()->update(['is_active' => false]);

        $categories = [
            [
                'name' => 'Пици',
                'slug' => 'pizza',
                'description' => 'Пици с пухкаво тесто, домашен доматен сос и пресни продукти.',
                'sort_order' => 1,
                'allows_extras' => true,
            ],
            [
                'name' => 'Сандвичи и Пърленки',
                'slug' => 'sandvici-i-pierlenki',
                'description' => 'Сандвичи с пица хлебче и топли пърленки, изпечени на момента.',
                'sort_order' => 2,
                'allows_extras' => true,
            ],
            [
                'name' => 'Напитки',
                'slug' => 'drinks',
                'description' => 'Студени напитки за всяка поръчка.',
                'sort_order' => 3,
                'allows_extras' => false,
            ],
            // Legacy categories – kept for records, hidden from menu
            [
                'name' => 'Сандвичи',
                'slug' => 'sandwich',
                'description' => 'Сандвичи с пица хлебче и пресни продукти.',
                'sort_order' => 99,
                'is_active' => false,
            ],
            [
                'name' => 'Пърленки',
                'slug' => 'parlenki',
                'description' => 'Топли пърленки, изпечени на момента.',
                'sort_order' => 99,
                'is_active' => false,
            ],
            [
                'name' => 'Салати',
                'slug' => 'salads',
                'description' => 'Свежи салати със сезонни зеленчуци.',
                'sort_order' => 99,
                'is_active' => false,
            ],
            [
                'name' => 'Десерти',
                'slug' => 'desserts',
                'description' => 'Сладки предложения за финал.',
                'sort_order' => 99,
                'is_active' => false,
            ],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => $category['slug']],
                array_merge($category, [
                    'is_active' => $category['is_active'] ?? true,
                    'allows_extras' => $category['allows_extras'] ?? false,
                    'seo_title' => $category['name'].' | Allo! Pizza',
                    'seo_description' => $category['description'],
                ])
            );
        }
    }
}
