<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\ProductVariant;

class ExtraWeight
{
    public static function for(Ingredient $ingredient, ProductVariant $variant): ?string
    {
        $base = $ingredient->portion_weight;

        if ($base === null || trim($base) === '') {
            return null;
        }

        $bonus = (int) ($variant->extra_weight_bonus_grams ?? 0);

        if ($bonus <= 0) {
            return $base;
        }

        if (! preg_match('/(\d+)/', $base, $matches)) {
            return $base;
        }

        $grams = (int) $matches[1] + $bonus;

        return preg_replace('/\d+/', (string) $grams, $base, 1);
    }
}
