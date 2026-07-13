<?php

use App\Support\ExtraIngredientCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'allows_extras')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('allows_extras')->nullable()->after('is_spicy');
            });
        }

        DB::table('product_ingredient')->delete();
        DB::table('ingredients')->delete();
        DB::table('ingredients')->insert(ExtraIngredientCatalog::seedRows());

        DB::table('categories')
            ->where('slug', 'pizza')
            ->update(['allows_extras' => true]);

        DB::table('categories')
            ->where('slug', 'sandvici-i-pierlenki')
            ->update(['allows_extras' => false]);

        DB::table('categories')
            ->where('slug', 'drinks')
            ->update(['allows_extras' => false]);

        $pizzaCategoryId = DB::table('categories')->where('slug', 'pizza')->value('id');

        if ($pizzaCategoryId) {
            DB::table('products')
                ->where('category_id', $pizzaCategoryId)
                ->update(['allows_extras' => null]);
        }

        DB::table('products')
            ->whereIn('slug', ['italianski-sandvich', 'kapreze-sandvich'])
            ->update(['allows_extras' => true]);

        $sandviciCategoryId = DB::table('categories')->where('slug', 'sandvici-i-pierlenki')->value('id');

        if ($sandviciCategoryId) {
            DB::table('products')
                ->where('category_id', $sandviciCategoryId)
                ->whereNotIn('slug', ['italianski-sandvich', 'kapreze-sandvich'])
                ->update(['allows_extras' => false]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'allows_extras')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('allows_extras');
            });
        }
    }
};
