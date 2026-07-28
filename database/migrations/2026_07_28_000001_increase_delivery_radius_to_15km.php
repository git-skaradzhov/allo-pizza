<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('store_settings')
            ->where('delivery_radius_km', '<', 15)
            ->update(['delivery_radius_km' => 15]);
    }

    public function down(): void
    {
        // Не намаляваме радиуса обратно — може да е променен ръчно след миграцията.
    }
};
