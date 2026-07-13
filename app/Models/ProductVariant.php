<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price',
        'extra_price_multiplier',
        'size_label',
        'weight',
        'diameter',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'extra_price_multiplier' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        $syncPromo = function (ProductVariant $variant): void {
            $product = $variant->relationLoaded('product')
                ? $variant->product
                : $variant->product()->first();

            $product?->syncPromoFlag();
        };

        static::saved($syncPromo);
        static::deleted($syncPromo);
    }
}
