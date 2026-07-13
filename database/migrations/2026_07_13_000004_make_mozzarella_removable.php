<?php

use App\Support\IngredientCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        IngredientCatalogSync::syncIngredients();
    }

    public function down(): void
    {
        //
    }
};
