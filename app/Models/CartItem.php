<?php

namespace App\Models;

use App\Enums\CartItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'item_type',
        'product_id',
        'product_variant_id',
        'lunch_menu_item_id',
        'item_name',
        'item_description',
        'item_image',
        'quantity',
        'unit_price',
        'total_price',
        'note',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => CartItemType::class,
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'options' => 'array',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lunchMenuItem(): BelongsTo
    {
        return $this->belongsTo(LunchMenuItem::class);
    }

    public function displayName(): string
    {
        if ($this->item_type === CartItemType::LunchItem) {
            return $this->item_name ?? 'Обедно предложение';
        }

        return $this->product?->name ?? $this->item_name ?? 'Продукт';
    }

    public function displayDescription(): ?string
    {
        if ($this->item_type === CartItemType::LunchItem) {
            return $this->item_description;
        }

        return null;
    }

    public function isLunchItem(): bool
    {
        return $this->item_type === CartItemType::LunchItem;
    }

    public function isPizzaBundleEligible(): bool
    {
        return app(\App\Services\PizzaBundlePromotionService::class)->isCartItemEligible($this);
    }
}
