<?php

namespace App\Support;

readonly class PizzaBundlePromotionResult
{
    /**
     * @param  array<int, array{product_name: string, unit_price: float, options: array<int, mixed>}>  $freeItems
     */
    public function __construct(
        public int $eligibleQuantity = 0,
        public int $freeCount = 0,
        public int $remainingUntilFree = 0,
        public float $discount = 0.0,
        public array $freeItems = [],
    ) {}

    public function isActive(): bool
    {
        return $this->freeCount >= 1;
    }
}
