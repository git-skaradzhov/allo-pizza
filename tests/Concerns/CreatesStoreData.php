<?php

namespace Tests\Concerns;

use App\Models\Category;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\WorkingHour;
use App\Support\DeliveryZone;

trait CreatesStoreData
{
    protected function createOpenStore(): StoreSetting
    {
        WorkingHour::query()->updateOrCreate(
            ['day_of_week' => now()->dayOfWeekIso],
            ['opens_at' => '00:00:00', 'closes_at' => '23:59:59', 'is_closed' => false],
        );

        return StoreSetting::query()->create([
            'store_name' => 'Allo! Pizza',
            'store_email' => 'admin@allopizza.test',
            'store_lat' => 43.8356,
            'store_lng' => 25.9657,
            'delivery_radius_km' => 7,
            'delivery_zone_polygon' => DeliveryZone::defaultPolygon(),
            'delivery_price' => 3,
            'delivery_inside_price' => 2,
            'delivery_outside_price' => 3,
            'free_delivery_over' => 30,
            'minimum_order_amount' => 0,
            'is_store_open' => true,
        ]);
    }

    protected function createProductWithVariant(float $basePrice = 10): Product
    {
        $category = Category::factory()->create(['is_active' => true]);

        return Product::factory()
            ->create(['category_id' => $category->id, 'is_active' => true, 'base_price' => $basePrice])
            ->fresh('variants');
    }
}
