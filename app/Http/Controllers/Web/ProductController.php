<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use App\Services\Meta\MetaPageEventRegistrar;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug, Request $request, MetaPageEventRegistrar $metaPageEvents): View
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with(['category', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'), 'ingredients', 'images'])
            ->firstOrFail();

        $metaPageEvents->queueViewContent($product, $request);

        $recipeIngredientIds = $product->ingredients->pluck('id');

        $removableIngredients = $product->ingredients
            ->filter(fn ($ingredient) => (bool) $ingredient->pivot->is_default && $ingredient->is_removable)
            ->values();

        $extraIngredients = $product->allowsExtras()
            ? Ingredient::query()
                ->where('is_extra', true)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('pages.product', compact('product', 'removableIngredients', 'extraIngredients', 'recipeIngredientIds'));
    }
}
