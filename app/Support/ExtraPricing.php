<?php

namespace App\Support;

use App\Models\Ingredient;
use App\Models\ProductVariant;

class ExtraPricing
{
    public static function priceFor(Ingredient $ingredient, ProductVariant $variant): float
    {
        return round(
            (float) $ingredient->price * (float) ($variant->extra_price_multiplier ?? 1),
            2
        );
    }
}
