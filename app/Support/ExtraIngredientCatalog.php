<?php

namespace App\Support;

class ExtraIngredientCatalog
{
    /**
     * @return array<int, array{name: string, price: float, portion_weight: string, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Моцарела', 'price' => 0.60, 'portion_weight' => '50 гр', 'sort_order' => 1],
            ['name' => 'Топено сирене', 'price' => 0.60, 'portion_weight' => '50 гр', 'sort_order' => 2],
            ['name' => 'Маслини рязани', 'price' => 0.60, 'portion_weight' => '50 гр', 'sort_order' => 3],
            ['name' => 'Синьо сирене', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 4],
            ['name' => 'Пармезан', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 5],
            ['name' => 'Чедър', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 6],
            ['name' => 'Пилешко филе', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 7],
            ['name' => 'Пеперони', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 8],
            ['name' => 'Еленски бут', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 9],
            ['name' => 'Шунка', 'price' => 1.00, 'portion_weight' => '50 гр', 'sort_order' => 10],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function seedRows(): array
    {
        $now = now();

        return collect(self::definitions())
            ->map(fn (array $ingredient) => array_merge($ingredient, [
                'is_removable' => false,
                'is_extra' => true,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]))
            ->all();
    }
}
