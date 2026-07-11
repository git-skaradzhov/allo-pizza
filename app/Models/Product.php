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
}
