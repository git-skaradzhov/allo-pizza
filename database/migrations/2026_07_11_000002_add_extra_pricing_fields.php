<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('extra_price_multiplier', 4, 2)->default(1.00)->after('price');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('portion_weight')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('extra_price_multiplier');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('portion_weight');
        });
    }
};
