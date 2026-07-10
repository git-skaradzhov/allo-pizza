<?php

namespace Database\Seeders;

use App\Models\NewMenuHighlight;
use App\Models\Product;
use Illuminate\Database\Seeder;

class NewMenuHighlightSeeder extends Seeder
{
    public function run(): void
    {
        $highlight = NewMenuHighlight::query()->firstOrCreate(
            ['title' => 'Ново в менюто'],
            [
                'description' => 'Открийте най-новите предложения в менюто на Allo! Pizza.',
                'message' => 'Свежи вкусове и актуални предложения, подбрани за вас.',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $productIds = Product::query()
            ->where('is_active', true)
            ->where('is_new', true)
            ->orderBy('sort_order')
            ->pluck('id');

        $syncData = $productIds
            ->mapWithKeys(fn ($id, $index) => [$id => ['sort_order' => $index + 1]])
            ->all();

        $highlight->products()->sync($syncData);
    }
}
