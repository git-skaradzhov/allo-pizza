<?php

namespace App\Services;

use App\Enums\DeliveryType;
use Illuminate\Validation\ValidationException;

class OrderPlacementValidator
{
    public function __construct(
        protected StoreService $storeService,
        protected DeliveryService $deliveryService,
    ) {}

    public function assertMinimumOrderAmount(float $subtotal): void
    {
        $minimum = (float) $this->storeService->settings()->minimum_order_amount;

        if ($subtotal < $minimum) {
            throw ValidationException::withMessages([
                'subtotal' => 'Минималната стойност на поръчката е '.number_format($minimum, 2, '.', '').' €.',
            ]);
        }
    }

    public function assertDeliveryLocation(DeliveryType $deliveryType, ?float $lat, ?float $lng): void
    {
        if ($deliveryType !== DeliveryType::Delivery) {
            return;
        }

        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages([
                'delivery_lat' => 'Моля, изберете адрес на картата.',
            ]);
        }

        if (! $this->deliveryService->isDeliverable($lat, $lng)) {
            throw ValidationException::withMessages([
                'delivery_address' => 'Адресът е извън зоната за доставка.',
            ]);
        }
    }
}
