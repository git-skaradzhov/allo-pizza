<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Support\ExtraIngredientCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_ingredient')->delete();
        Ingredient::query()->delete();

        foreach (ExtraIngredientCatalog::definitions() as $ingredient) {
            Ingredient::query()->updateOrCreate(
                ['name' => $ingredient['name']],
                array_merge($ingredient, [
                    'is_removable' => false,
                    'is_extra' => true,
                    'is_active' => true,
                ])
            );
        }
    }
}
