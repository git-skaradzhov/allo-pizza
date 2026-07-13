<?php

namespace App\Support;

class BaseIngredientCatalog
{
    /**
     * @return array<int, array{name: string, is_removable: bool, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Доматен сос', 'is_removable' => false, 'sort_order' => 1],
            ['name' => 'Моцарела', 'is_removable' => true, 'sort_order' => 2],
            ['name' => 'Босилек', 'is_removable' => true, 'sort_order' => 3],
            ['name' => 'Пармезан', 'is_removable' => true, 'sort_order' => 4],
            ['name' => 'Зехтин', 'is_removable' => true, 'sort_order' => 5],
            ['name' => 'Пеперони', 'is_removable' => true, 'sort_order' => 6],
            ['name' => 'Сметана', 'is_removable' => true, 'sort_order' => 7],
            ['name' => 'Топено сирене', 'is_removable' => true, 'sort_order' => 8],
            ['name' => 'Синьо сирене', 'is_removable' => true, 'sort_order' => 9],
            ['name' => 'Шунка', 'is_removable' => true, 'sort_order' => 10],
            ['name' => 'Гъби', 'is_removable' => true, 'sort_order' => 11],
            ['name' => 'Чушка', 'is_removable' => true, 'sort_order' => 12],
            ['name' => 'Прошуто крудо', 'is_removable' => true, 'sort_order' => 13],
            ['name' => 'Рукола', 'is_removable' => true, 'sort_order' => 14],
            ['name' => 'Домат чери', 'is_removable' => true, 'sort_order' => 15],
            ['name' => 'Балсамико', 'is_removable' => true, 'sort_order' => 16],
            ['name' => 'Пилешко филе', 'is_removable' => true, 'sort_order' => 17],
            ['name' => 'Чедър', 'is_removable' => true, 'sort_order' => 18],
            ['name' => 'Еленски бут', 'is_removable' => true, 'sort_order' => 19],
            ['name' => 'Манатарки', 'is_removable' => true, 'sort_order' => 20],
            ['name' => 'Мащерка', 'is_removable' => true, 'sort_order' => 21],
            ['name' => 'Трюфел', 'is_removable' => true, 'sort_order' => 22],
            ['name' => 'Салата микс', 'is_removable' => true, 'sort_order' => 23],
            ['name' => 'Маслини', 'is_removable' => true, 'sort_order' => 24],
            ['name' => 'Пушен бекон', 'is_removable' => true, 'sort_order' => 25],
            ['name' => 'Карамелизиран лук', 'is_removable' => true, 'sort_order' => 26],
            ['name' => 'Сос барбекю', 'is_removable' => true, 'sort_order' => 27],
            ['name' => 'Пица хлебче', 'is_removable' => false, 'sort_order' => 28],
            ['name' => 'Прошуто котто', 'is_removable' => true, 'sort_order' => 29],
            ['name' => 'Пресен домат', 'is_removable' => true, 'sort_order' => 30],
            ['name' => 'Дресинг', 'is_removable' => true, 'sort_order' => 31],
            ['name' => 'Сос айоли', 'is_removable' => true, 'sort_order' => 32],
            ['name' => 'Босилково песто', 'is_removable' => true, 'sort_order' => 33],
        ];
    }
}
