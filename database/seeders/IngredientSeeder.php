<?php

namespace Database\Seeders;

use App\Support\IngredientCatalogSync;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        IngredientCatalogSync::syncAll();
    }
}
