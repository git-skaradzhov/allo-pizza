<?php

namespace App\Services;

use App\Models\PromoCode;
use App\Support\PizzaBundlePromotionResult;

class ApiOrderPricingService
{
    public function __construct(
        protected PizzaBundlePromotionService $pizzaBundlePromotionService,
        protected PromoService $promoService,
    ) {}

    /**
     * @param  array<int, array{
     *     category_slug: ?string,
     *     diameter: int,
     *     unit_price: float,
     *     quantity: int,
     *     product_name: string,
     *     options?: array<int, mixed>,
     *     is_product?: bool,
     * }>  $lines
     * @return array{
     *     subtotal: float,
     *     bundle: PizzaBundlePromotionResult,
     *     bundleDiscount: float,
     *     promoDiscount: float,
     *     totalDiscount: float,
     *     appliedPromo: ?PromoCode,
     *     promoIgnored: bool,
     *     promoInvalid: bool,
     * }
     */
    public function summarize(float $subtotal, array $lines, ?string $promoCode = null): array
    {
        $bundle = $this->pizzaBundlePromotionService->evaluateLines($lines);
        $bundleDiscount = $bundle->discount;

        $appliedPromo = null;
        $promoDiscount = 0.0;
        $promoIgnored = false;
        $promoInvalid = false;

        if ($promoCode) {
            $promo = $this->promoService->resolve($promoCode);

            if ($bundleDiscount > 0) {
                $promoIgnored = $promo && $promo->isCurrentlyValid();
            } elseif ($promo && $promo->isCurrentlyValid() && $promo->meetsMinimum($subtotal)) {
                $appliedPromo = $promo;
                $promoDiscount = $promo->discountFor($subtotal);
            } else {
                $promoInvalid = true;
            }
        }

        return [
            'subtotal' => $subtotal,
            'bundle' => $bundle,
            'bundleDiscount' => $bundleDiscount,
            'promoDiscount' => $promoDiscount,
            'totalDiscount' => round($bundleDiscount + $promoDiscount, 2),
            'appliedPromo' => $appliedPromo,
            'promoIgnored' => $promoIgnored,
            'promoInvalid' => $promoInvalid,
        ];
    }
}
