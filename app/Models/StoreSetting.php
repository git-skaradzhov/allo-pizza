<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_name',
        'store_phone',
        'store_phone_secondary',
        'store_email',
        'store_address',
        'store_lat',
        'store_lng',
        'delivery_radius_km',
        'delivery_zone_polygon',
        'delivery_price',
        'delivery_inside_price',
        'delivery_outside_price',
        'free_delivery_over',
        'minimum_order_amount',
        'average_delivery_time',
        'is_store_open',
        'bundle_promotion_enabled',
        'bundle_promotion_category_ids',
        'closed_message',
        'seo_title_suffix',
        'seo_default_description',
        'seo_default_image',
        'organization_description',
        'organization_logo',
        'google_analytics_id',
        'google_tag_manager_id',
        'google_site_verification',
        'bing_site_verification',
        'meta_pixel_id',
    ];

    protected function casts(): array
    {
        return [
            'store_lat' => 'decimal:7',
            'store_lng' => 'decimal:7',
            'delivery_radius_km' => 'decimal:2',
            'delivery_zone_polygon' => 'array',
            'delivery_price' => 'decimal:2',
            'delivery_inside_price' => 'decimal:2',
            'delivery_outside_price' => 'decimal:2',
            'free_delivery_over' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'is_store_open' => 'boolean',
            'bundle_promotion_enabled' => 'boolean',
            'bundle_promotion_category_ids' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'store_name' => 'Allo! Pizza',
            'store_phone' => '0899 679 006',
            'store_phone_secondary' => '0899 679 710',
            'store_email' => 'allopizza@abv.bg',
            'store_address' => 'гр. Русе, ул. „Мария Луиза“, 22',
            'store_lat' => 43.8407475,
            'store_lng' => 25.9549665,
            'delivery_radius_km' => 15,
            'delivery_zone_polygon' => \App\Support\DeliveryZone::defaultPolygon(),
            'delivery_price' => 1.79,
            'delivery_inside_price' => 2.00,
            'delivery_outside_price' => 3.00,
            'minimum_order_amount' => 7.67,
            'is_store_open' => true,
            'bundle_promotion_enabled' => true,
            'bundle_promotion_category_ids' => static::defaultBundlePromotionCategoryIds(),
        ]);
    }

    /**
     * @return array<int, int>|null
     */
    protected static function defaultBundlePromotionCategoryIds(): ?array
    {
        $pizzaCategoryId = Category::query()
            ->where('slug', config('promotions.pizza_bundle.eligible_category_slug', 'pizza'))
            ->value('id');

        return $pizzaCategoryId ? [(int) $pizzaCategoryId] : null;
    }

    public function isBundlePromotionEnabled(): bool
    {
        return (bool) ($this->bundle_promotion_enabled ?? true);
    }

    /**
     * @return array<int, int>
     */
    public function bundlePromotionCategoryIds(): array
    {
        $ids = collect($this->bundle_promotion_category_ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        $pizzaCategoryId = Category::query()
            ->where('slug', config('promotions.pizza_bundle.eligible_category_slug', 'pizza'))
            ->value('id');

        return $pizzaCategoryId ? [(int) $pizzaCategoryId] : [];
    }

    public function bundlePromotionLabel(): string
    {
        return (string) config('promotions.pizza_bundle.label', '4+1 промо');
    }

    public function phoneNumbers(): array
    {
        return array_values(array_filter([
            $this->store_phone,
            $this->store_phone_secondary,
        ]));
    }

    public static function normalizePhone(string $phone): string
    {
        return preg_replace('/\s+/', '', $phone) ?? $phone;
    }
}
