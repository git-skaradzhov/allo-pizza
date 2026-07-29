<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_variants', 'extra_weight_bonus_grams')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->unsignedSmallInteger('extra_weight_bonus_grams')->default(0)->after('extra_price_multiplier');
            });
        }

        DB::table('product_variants')
            ->where(function ($query) {
                $query->where('diameter', '45')
                    ->orWhere('diameter', 45)
                    ->orWhere('size_label', '45 см')
                    ->orWhere('name', '45 см');
            })
            ->update(['extra_weight_bonus_grams' => 25]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'extra_weight_bonus_grams')) {
            Schema::table('product_variants', function (Blueprint $table) {
                $table->dropColumn('extra_weight_bonus_grams');
            });
        }
    }
};
