<?php

namespace Database\Seeders;

use App\Models\StoreSetting;
use App\Support\DeliveryZone;
use App\Support\Money;
use Illuminate\Database\Seeder;

class StoreSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = StoreSetting::query()->first() ?? new StoreSetting();

        $settings->fill([
            'store_name' => 'Allo! Pizza',
            'store_phone' => '0899 679 006',
            'store_phone_secondary' => '0899 679 710',
            'store_email' => 'allopizza@abv.bg',
            'store_address' => 'гр. Русе, ул. „Мария Луиза“, 22',
            'store_lat' => 43.8407475,
            'store_lng' => 25.9549665,
            'delivery_radius_km' => 7.00,
            'delivery_zone_polygon' => DeliveryZone::defaultPolygon(),
            'delivery_price' => Money::fromBgn(3.50),
            'delivery_inside_price' => 2.00,
            'delivery_outside_price' => 3.00,
            'free_delivery_over' => 30.00,
            'minimum_order_amount' => Money::fromBgn(15.00),
            'average_delivery_time' => 30,
            'is_store_open' => true,
            'closed_message' => null,
        ])->save();
    }
}
