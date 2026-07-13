<?php

use App\Support\IngredientCatalogSync;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        IngredientCatalogSync::syncAll();
    }

    public function down(): void
    {
        // Intentionally left empty — restoring removable ingredients is a one-way data migration.
    }
};
