<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('allows_extras')->default(true)->after('is_active');
        });

        DB::table('categories')
            ->where('slug', 'drinks')
            ->update(['allows_extras' => false]);

        $drinkProductIds = DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('categories.slug', 'drinks')
            ->pluck('products.id');

        if ($drinkProductIds->isNotEmpty()) {
            DB::table('product_ingredient')
                ->whereIn('product_id', $drinkProductIds)
                ->delete();

            DB::table('cart_items')
                ->whereIn('product_id', $drinkProductIds)
                ->orderBy('id')
                ->chunkById(100, function ($items) {
                    foreach ($items as $item) {
                        $options = json_decode($item->options ?? '[]', true);

                        if (! is_array($options)) {
                            continue;
                        }

                        $filtered = array_values(array_filter(
                            $options,
                            fn (array $option) => ($option['type'] ?? '') !== 'extra_added'
                        ));

                        if (count($filtered) !== count($options)) {
                            DB::table('cart_items')
                                ->where('id', $item->id)
                                ->update(['options' => json_encode($filtered)]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('allows_extras');
        });
    }
};
