<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('bundle_promotion_enabled')->default(true)->after('is_store_open');
            $table->json('bundle_promotion_category_ids')->nullable()->after('bundle_promotion_enabled');
        });

        $pizzaCategoryId = DB::table('categories')
            ->where('slug', 'pizza')
            ->value('id');

        if ($pizzaCategoryId) {
            DB::table('store_settings')->update([
                'bundle_promotion_category_ids' => json_encode([(int) $pizzaCategoryId]),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'bundle_promotion_enabled',
                'bundle_promotion_category_ids',
            ]);
        });
    }
};
