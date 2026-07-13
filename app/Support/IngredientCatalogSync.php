<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class IngredientCatalogSync
{
    public static function syncAll(): void
    {
        self::cleanupLegacyRecords();
        self::syncIngredients();
        self::syncProductRecipes();
    }

    private static function cleanupLegacyRecords(): void
    {
        Ingredient::query()
            ->where('name', 'like', '%(добавка)')
            ->delete();

        $overlapNames = collect(ExtraIngredientCatalog::definitions())->pluck('name');

        Ingredient::query()
            ->whereIn('name', $overlapNames)
            ->where('is_extra', false)
            ->delete();
    }

    public static function syncIngredients(): void
    {
        $extrasByName = collect(ExtraIngredientCatalog::definitions())->keyBy('name');
        $baseByName = collect(BaseIngredientCatalog::definitions())->keyBy('name');

        foreach ($extrasByName as $name => $extra) {
            $base = $baseByName->get($name);

            Ingredient::query()->updateOrCreate(
                ['name' => $name],
                [
                    'price' => $extra['price'],
                    'portion_weight' => $extra['portion_weight'],
                    'sort_order' => $extra['sort_order'],
                    'is_removable' => $base['is_removable'] ?? false,
                    'is_extra' => true,
                    'is_active' => true,
                ]
            );
        }

        foreach ($baseByName as $name => $base) {
            if ($extrasByName->has($name)) {
                continue;
            }

            Ingredient::query()->updateOrCreate(
                ['name' => $name],
                [
                    'price' => 0,
                    'portion_weight' => null,
                    'sort_order' => $base['sort_order'],
                    'is_removable' => $base['is_removable'],
                    'is_extra' => false,
                    'is_active' => true,
                ]
            );
        }
    }

    public static function syncProductRecipes(): void
    {
        $ingredientMap = Ingredient::query()->pluck('id', 'name');

        foreach (ProductRecipeCatalog::recipesBySlug() as $slug => $ingredientNames) {
            $product = Product::query()->where('slug', $slug)->first();

            if (! $product) {
                continue;
            }

            $ingredientIds = collect($ingredientNames)
                ->map(fn (string $name) => $ingredientMap[$name] ?? null)
                ->filter()
                ->mapWithKeys(fn (int $id) => [$id => ['is_default' => true]])
                ->all();

            $product->ingredients()->sync($ingredientIds);
        }
    }
}
