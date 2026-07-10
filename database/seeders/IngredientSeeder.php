<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            ['name' => 'Доматен сос', 'price' => 0, 'is_removable' => false, 'is_extra' => false, 'sort_order' => 1],
            ['name' => 'Моцарела', 'price' => 0, 'is_removable' => false, 'is_extra' => false, 'sort_order' => 2],
            ['name' => 'Босилек', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 3],
            ['name' => 'Пармезан', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 4],
            ['name' => 'Зехтин', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 5],
            ['name' => 'Пеперони', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 6],
            ['name' => 'Сметана', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 7],
            ['name' => 'Топено сирене', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 8],
            ['name' => 'Синьо сирене', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 9],
            ['name' => 'Шунка', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 10],
            ['name' => 'Гъби', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 11],
            ['name' => 'Чушка', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 12],
            ['name' => 'Прошуто крудо', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 13],
            ['name' => 'Рукола', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 14],
            ['name' => 'Домат чери', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 15],
            ['name' => 'Балсамико', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 16],
            ['name' => 'Пилешко филе', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 17],
            ['name' => 'Чедър', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 18],
            ['name' => 'Еленски бут', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 19],
            ['name' => 'Манатарки', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 20],
            ['name' => 'Мащерка', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 21],
            ['name' => 'Трюфел', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 22],
            ['name' => 'Салата микс', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 23],
            ['name' => 'Маслини', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 24],
            ['name' => 'Пушен бекон', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 25],
            ['name' => 'Карамелизиран лук', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 26],
            ['name' => 'Сос барбекю', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 27],
            ['name' => 'Пица хлебче', 'price' => 0, 'is_removable' => false, 'is_extra' => false, 'sort_order' => 28],
            ['name' => 'Прошуто котто', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 29],
            ['name' => 'Пресен домат', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 30],
            ['name' => 'Дресинг', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 31],
            ['name' => 'Сос айоли', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 32],
            ['name' => 'Босилково песто', 'price' => 0, 'is_removable' => true, 'is_extra' => false, 'sort_order' => 33],

            // Допълнителни добавки (50 гр)
            ['name' => 'Моцарела (добавка)', 'price' => 0.60, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 101],
            ['name' => 'Топено сирене (добавка)', 'price' => 0.60, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 102],
            ['name' => 'Маслини рязани', 'price' => 0.60, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 103],
            ['name' => 'Синьо сирене (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 104],
            ['name' => 'Пармезан (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 105],
            ['name' => 'Чедър (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 106],
            ['name' => 'Пилешко филе (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 107],
            ['name' => 'Пеперони (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 108],
            ['name' => 'Еленски бут (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 109],
            ['name' => 'Шунка (добавка)', 'price' => 1.00, 'portion_weight' => '50 гр', 'is_removable' => false, 'is_extra' => true, 'sort_order' => 110],
        ];

        foreach ($ingredients as $ingredient) {
            Ingredient::query()->updateOrCreate(
                ['name' => $ingredient['name']],
                array_merge($ingredient, [
                    'is_active' => true,
                ])
            );
        }
    }
}
