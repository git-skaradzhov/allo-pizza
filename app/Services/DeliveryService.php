<?php

namespace App\Services;

use App\Models\StoreSetting;
use App\Support\DeliveryZone;

class DeliveryService
{
    public function __construct(
        protected StoreService $storeService,
    ) {}

    public function isWithinZone(float $lat, float $lng): bool
    {
        return $this->pointInPolygon($lat, $lng, $this->zonePolygon());
    }

    public function distanceKm(float $lat, float $lng): float
    {
        $settings = $this->storeService->settings();

        return $this->haversineDistance(
            (float) $settings->store_lat,
            (float) $settings->store_lng,
            $lat,
            $lng
        );
    }

    public function deliveryPrice(float $subtotal, ?float $lat = null, ?float $lng = null): float
    {
        $settings = $this->storeService->settings();

        if ($lat !== null && $lng !== null && $this->requiresDeliveryQuote($lat, $lng)) {
            return 0.0;
        }

        if ($settings->free_delivery_over && $subtotal >= (float) $settings->free_delivery_over) {
            return 0;
        }

        if ($lat !== null && $lng !== null) {
            return (float) $settings->delivery_inside_price;
        }

        return (float) $settings->delivery_inside_price;
    }

    public function requiresDeliveryQuote(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        return ! $this->isWithinZone($lat, $lng);
    }

    /**
     * @return array<int, array{lat: float, lng: float}>
     */
    public function zonePolygon(): array
    {
        $settings = $this->storeService->settings();
        $polygon = $settings->delivery_zone_polygon ?? [];

        return ! empty($polygon) ? $polygon : DeliveryZone::defaultPolygon();
    }

    public function isDeliverable(float $lat, float $lng): bool
    {
        $settings = $this->storeService->settings();
        $maxRadius = $settings->delivery_radius_km;

        if ($maxRadius === null || (float) $maxRadius <= 0) {
            return true;
        }

        return $this->distanceKm($lat, $lng) <= (float) $maxRadius;
    }

    /**
     * @param  array<int, array{lat: float, lng: float}>  $polygon
     */
    protected function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $count = count($polygon);

        if ($count < 3) {
            return false;
        }

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $yi = (float) $polygon[$i]['lat'];
            $xi = (float) $polygon[$i]['lng'];
            $yj = (float) $polygon[$j]['lat'];
            $xj = (float) $polygon[$j]['lng'];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
