<?php

namespace App\Services;

use App\Enums\CartItemType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\StoreSetting;
use App\Support\PizzaBundlePromotionResult;

class PizzaBundlePromotionService
{
    public function __construct(
        protected StoreService $storeService,
    ) {}

    public function evaluate(Cart $cart): PizzaBundlePromotionResult
    {
        $cart->loadMissing(['items.product.category', 'items.variant']);

        $lines = [];

        foreach ($cart->items as $item) {
            $lines[] = $this->lineFromCartItem($item);
        }

        return $this->evaluateLines($lines);
    }

    public function isLineEligible(array $line, ?StoreSetting $settings = null): bool
    {
        $settings ??= $this->storeService->settings();

        if (! $settings->isBundlePromotionEnabled()) {
            return false;
        }

        $categoryIds = $settings->bundlePromotionCategoryIds();

        if ($categoryIds === []) {
            return false;
        }

        return $this->matchesEligibleLine($line, $categoryIds);
    }

    public function isCartItemEligible(CartItem $item, ?StoreSetting $settings = null): bool
    {
        $item->loadMissing(['product.category', 'variant']);

        return $this->isLineEligible($this->lineFromCartItem($item), $settings);
    }

    /**
     * @param  array<int, array{
     *     category_id?: int|null,
     *     category_slug: ?string,
     *     diameter: int,
     *     unit_price: float,
     *     quantity: int,
     *     product_name: string,
     *     options?: array<int, mixed>,
     *     is_product?: bool,
     * }>  $lines
     */
    public function evaluateLines(array $lines): PizzaBundlePromotionResult
    {
        $settings = $this->storeService->settings();

        if (! $settings->isBundlePromotionEnabled()) {
            return new PizzaBundlePromotionResult;
        }

        $groupSize = (int) config('promotions.pizza_bundle.group_size', 5);
        $categoryIds = $settings->bundlePromotionCategoryIds();

        if ($categoryIds === []) {
            return new PizzaBundlePromotionResult;
        }

        $units = [];

        foreach ($lines as $line) {
            if (! $this->matchesEligibleLine($line, $categoryIds)) {
                continue;
            }

            $unitPrice = (float) $line['unit_price'];

            for ($i = 0; $i < $line['quantity']; $i++) {
                $units[] = [
                    'product_name' => $line['product_name'],
                    'unit_price' => $unitPrice,
                    'options' => $line['options'] ?? [],
                ];
            }
        }

        $eligibleQuantity = count($units);
        $freeCount = intdiv($eligibleQuantity, $groupSize);
        $remainingUntilFree = $eligibleQuantity % $groupSize === 0
            ? 0
            : $groupSize - ($eligibleQuantity % $groupSize);

        if ($freeCount === 0) {
            return new PizzaBundlePromotionResult(
                eligibleQuantity: $eligibleQuantity,
                freeCount: 0,
                remainingUntilFree: $remainingUntilFree,
            );
        }

        usort($units, fn (array $a, array $b) => $a['unit_price'] <=> $b['unit_price']);

        $freeItems = array_slice($units, 0, $freeCount);
        $discount = round(array_sum(array_column($freeItems, 'unit_price')), 2);

        return new PizzaBundlePromotionResult(
            eligibleQuantity: $eligibleQuantity,
            freeCount: $freeCount,
            remainingUntilFree: 0,
            discount: $discount,
            freeItems: $freeItems,
        );
    }

    /**
     * @return array{
     *     category_id: ?int,
     *     category_slug: ?string,
     *     diameter: int,
     *     unit_price: float,
     *     quantity: int,
     *     product_name: string,
     *     options: array<int, mixed>,
     *     is_product: bool,
     * }
     */
    protected function lineFromCartItem(CartItem $item): array
    {
        return [
            'category_id' => $item->product?->category_id,
            'category_slug' => $item->product?->category?->slug,
            'diameter' => (int) ($item->variant?->diameter ?? 0),
            'unit_price' => (float) $item->unit_price,
            'quantity' => $item->quantity,
            'product_name' => $item->displayName(),
            'options' => $item->options ?? [],
            'is_product' => $item->item_type === CartItemType::Product,
        ];
    }

    /**
     * @param  array{
     *     category_id?: int|null,
     *     category_slug: ?string,
     *     diameter: int,
     *     unit_price: float,
     *     quantity: int,
     *     product_name: string,
     *     options?: array<int, mixed>,
     *     is_product?: bool,
     * }  $line
     * @param  array<int, int>  $categoryIds
     */
    protected function matchesEligibleLine(array $line, array $categoryIds): bool
    {
        if (($line['is_product'] ?? true) === false) {
            return false;
        }

        $categoryId = (int) ($line['category_id'] ?? 0);

        if (! in_array($categoryId, $categoryIds, true)) {
            return false;
        }

        $pizzaSlug = config('promotions.pizza_bundle.eligible_category_slug', 'pizza');
        $eligibleDiameter = (int) config('promotions.pizza_bundle.eligible_diameter', 30);

        if (($line['category_slug'] ?? null) === $pizzaSlug) {
            return (int) ($line['diameter'] ?? 0) === $eligibleDiameter;
        }

        return true;
    }
}
