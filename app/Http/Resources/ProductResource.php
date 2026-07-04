<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'base_price' => (float) $this->base_price,
            'old_price' => $this->old_price ? (float) $this->old_price : null,
            'image' => $this->image,
            'image_urls' => [
                'small' => product_image_url($this->image, product_image_tier_size('small')),
                'medium' => product_image_url($this->image, product_image_tier_size('medium')),
                'large' => product_image_url($this->image, product_image_tier_size('large')),
            ],
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_promo' => $this->is_promo,
            'is_new' => $this->is_new,
            'is_spicy' => $this->is_spicy,
            'sort_order' => $this->sort_order,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'ingredients' => $this->whenLoaded('ingredients', fn () => $this->ingredients->map(fn ($ingredient) => [
                'id' => $ingredient->id,
                'name' => $ingredient->name,
                'price' => (float) $ingredient->price,
                'is_removable' => $ingredient->is_removable,
                'is_extra' => $ingredient->is_extra,
                'is_default' => (bool) $ingredient->pivot->is_default,
            ])),
        ];
    }
}
