<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class NewMenuHighlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'message',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'new_menu_highlight_product')
            ->withPivot('sort_order');
    }

    public function visibleProducts(): Collection
    {
        return $this->products()
            ->where('products.is_active', true)
            ->with('variants')
            ->orderByPivot('sort_order')
            ->get();
    }

    public static function isPublished(): bool
    {
        return static::query()
            ->where('new_menu_highlights.is_active', true)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('new_menu_highlight_product')
                    ->join('products', 'products.id', '=', 'new_menu_highlight_product.product_id')
                    ->whereColumn('new_menu_highlight_product.new_menu_highlight_id', 'new_menu_highlights.id')
                    ->where('products.is_active', true);
            })
            ->exists();
    }
}
