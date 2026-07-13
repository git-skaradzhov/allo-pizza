<?php

namespace App\Models;

use App\Concerns\HasSeoFields;
use App\Contracts\SeoMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model implements SeoMeta
{
    use HasFactory;
    use HasSeoFields;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'base_price',
        'old_price',
        'image',
        'image_alt',
        'is_active',
        'is_featured',
        'is_promo',
        'is_new',
        'is_spicy',
        'sort_order',
        'seo_title',
        'seo_description',
        'canonical_url',
        'meta_robots',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'focus_keyword',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_promo' => 'boolean',
            'is_new' => 'boolean',
            'is_spicy' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            $product->is_promo = $product->isDiscounted();
        });
    }

    public function syncPromoFlag(): void
    {
        $isPromo = $this->isDiscounted();

        if ($this->is_promo === $isPromo) {
            return;
        }

        $this->is_promo = $isPromo;
        $this->saveQuietly();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredient')
            ->withPivot('is_default');
    }

    public function lunchMenus(): BelongsToMany
    {
        return $this->belongsToMany(LunchMenu::class, 'lunch_menu_product');
    }

    public function newMenuHighlights(): BelongsToMany
    {
        return $this->belongsToMany(NewMenuHighlight::class, 'new_menu_highlight_product')
            ->withPivot('sort_order');
    }

    public function imageUrl(int $size = 650): ?string
    {
        return product_image_url($this->image, $size);
    }

    public function allowsExtras(): bool
    {
        return (bool) $this->category?->allows_extras;
    }

    public function allowsNotes(): bool
    {
        return $this->allowsExtras();
    }

    public function isBundlePromotionEligible(): bool
    {
        return app(\App\Services\PizzaBundlePromotionService::class)->isProductEligible($this);
    }

    public function isDiscounted(): bool
    {
        if ($this->old_price === null) {
            return false;
        }

        return (float) $this->old_price > $this->lowestPrice();
    }

    public function lowestPrice(): float
    {
        $prices = $this->variantPrices();

        return $prices->isNotEmpty()
            ? (float) $prices->min()
            : (float) $this->base_price;
    }

    public function highestPrice(): float
    {
        $prices = $this->variantPrices();

        return $prices->isNotEmpty()
            ? (float) $prices->max()
            : (float) $this->base_price;
    }

    public function variantOldPrice(ProductVariant $variant): ?float
    {
        if (! $this->isDiscounted()) {
            return null;
        }

        $ratio = $this->discountRatio();

        return round((float) $variant->price * $ratio, 2);
    }

    public function oldPriceForAmount(float $currentPrice): ?float
    {
        if (! $this->isDiscounted()) {
            return null;
        }

        return round($currentPrice * $this->discountRatio(), 2);
    }

    public function savingsForAmount(float $currentPrice): float
    {
        $oldPrice = $this->oldPriceForAmount($currentPrice);

        return $oldPrice !== null
            ? round($oldPrice - $currentPrice, 2)
            : 0.0;
    }

    /**
     * @return array{
     *     current_label: string,
     *     old_label: ?string,
     *     savings: ?float,
     *     savings_max: ?float,
     *     has_range: bool,
     * }
     */
    public function priceSummary(): array
    {
        $min = $this->lowestPrice();
        $max = $this->highestPrice();
        $hasVariants = $this->variantPrices()->isNotEmpty();
        $hasRange = $min !== $max;

        $fromPrefix = $hasVariants ? '' : 'от ';

        $currentLabel = $hasRange
            ? money($min).' – '.money($max)
            : $fromPrefix.money($min);

        if (! $this->isDiscounted()) {
            return [
                'current_label' => $currentLabel,
                'old_label' => null,
                'savings' => null,
                'savings_max' => null,
                'has_range' => $hasRange,
            ];
        }

        $ratio = $this->discountRatio();
        $oldMin = round($min * $ratio, 2);
        $oldMax = round($max * $ratio, 2);

        $oldLabel = $hasRange
            ? money($oldMin).' – '.money($oldMax)
            : $fromPrefix.money($oldMin);

        $savings = round($oldMin - $min, 2);
        $savingsMax = $hasRange ? round($oldMax - $max, 2) : null;

        return [
            'current_label' => $currentLabel,
            'old_label' => $oldLabel,
            'savings' => $savings,
            'savings_max' => $savingsMax,
            'has_range' => $hasRange,
        ];
    }

    private function discountRatio(): float
    {
        $basePrice = (float) $this->base_price;

        if ($basePrice <= 0) {
            return 1.0;
        }

        return (float) $this->old_price / $basePrice;
    }

    private function variantPrices(): \Illuminate\Support\Collection
    {
        $variants = $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get();

        return $variants->pluck('price')->filter()->map(fn ($price) => (float) $price);
    }
}
