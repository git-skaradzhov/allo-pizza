<?php

namespace Tests\Unit;

use App\Models\StoreSetting;
use App\Services\DeliveryService;
use App\Support\DeliveryZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function createSettings(array $overrides = []): StoreSetting
    {
        return StoreSetting::query()->create(array_merge([
            'store_name' => 'Allo! Pizza',
            'store_lat' => 43.8407475,
            'store_lng' => 25.9549665,
            'delivery_zone_polygon' => DeliveryZone::defaultPolygon(),
            'delivery_price' => 3.00,
            'delivery_inside_price' => 2.00,
            'delivery_outside_price' => 3.00,
            'free_delivery_over' => 30.00,
            'minimum_order_amount' => 0,
            'is_store_open' => true,
        ], $overrides));
    }

    public function test_point_inside_polygon_is_within_zone(): void
    {
        $this->createSettings();

        $service = app(DeliveryService::class);

        $this->assertTrue($service->isWithinZone(43.8407468, 25.9536970));
    }

    public function test_point_outside_polygon_is_not_within_zone(): void
    {
        $this->createSettings();

        $service = app(DeliveryService::class);

        $this->assertFalse($service->isWithinZone(43.9000, 26.1000));
    }

    public function test_delivery_price_uses_inside_and_outside_prices(): void
    {
        $this->createSettings();

        $service = app(DeliveryService::class);

        $this->assertSame(2.0, $service->deliveryPrice(20, 43.8407468, 25.9536970));
        $this->assertSame(0.0, $service->deliveryPrice(20, 43.9000, 26.1000));
        $this->assertTrue($service->requiresDeliveryQuote(43.9000, 26.1000));
        $this->assertFalse($service->requiresDeliveryQuote(43.8407468, 25.9536970));
    }

    public function test_free_delivery_over_applies_only_inside_zone(): void
    {
        $this->createSettings();

        $service = app(DeliveryService::class);

        $this->assertSame(0.0, $service->deliveryPrice(30, 43.8407468, 25.9536970));
        $this->assertSame(0.0, $service->deliveryPrice(30, 43.9000, 26.1000));
        $this->assertTrue($service->requiresDeliveryQuote(43.9000, 26.1000));
    }

    public function test_default_polygon_is_used_when_database_value_missing(): void
    {
        $this->createSettings(['delivery_zone_polygon' => null]);

        $service = app(DeliveryService::class);

        $this->assertNotEmpty($service->zonePolygon());
        $this->assertTrue($service->isWithinZone(43.8407468, 25.9536970));
    }

    public function test_is_deliverable_respects_delivery_radius(): void
    {
        $this->createSettings(['delivery_radius_km' => 5]);

        $service = app(DeliveryService::class);

        $this->assertTrue($service->isDeliverable(43.8407468, 25.9536970));
        $this->assertFalse($service->isDeliverable(43.9000, 26.1000));
    }

    public function test_is_deliverable_allows_all_locations_when_radius_is_zero(): void
    {
        $this->createSettings(['delivery_radius_km' => 0]);

        $service = app(DeliveryService::class);

        $this->assertTrue($service->isDeliverable(43.9000, 26.1000));
    }
}
