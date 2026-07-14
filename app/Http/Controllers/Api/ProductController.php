<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with([
                'category',
                'variants' => fn ($query) => $query->where('is_active', true),
                'ingredients',
            ])
            ->orderBy('sort_order')
            ->get();

        return ProductResource::collection($products);
    }

    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'variants' => fn ($query) => $query->where('is_active', true),
            'ingredients',
        ]);

        return new ProductResource($product);
    }
}
