<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\PromoCode;
use App\Support\PizzaBundlePromotionResult;

class CartPricingService
{
    public function __construct(
        protected PizzaBundlePromotionService $pizzaBundlePromotionService,
        protected PromoService $promoService,
    ) {}

    /**
     * @return array{
     *     subtotal: float,
     *     bundle: PizzaBundlePromotionResult,
     *     bundleDiscount: float,
     *     promoDiscount: float,
     *     totalDiscount: float,
     *     appliedPromo: ?PromoCode,
     *     promoIgnored: bool,
     * }
     */
    public function summarize(Cart $cart): array
    {
        $subtotal = (float) $cart->items->sum('total_price');
        $bundle = $this->pizzaBundlePromotionService->evaluate($cart);
        $bundleDiscount = $bundle->discount;

        $sessionPromo = $this->promoService->applied();
        $promoIgnored = false;

        if ($bundleDiscount > 0) {
            $promoDiscount = 0.0;
            $appliedPromo = null;
            $promoIgnored = $sessionPromo !== null;
        } else {
            $appliedPromo = $sessionPromo && $sessionPromo->meetsMinimum($subtotal) ? $sessionPromo : null;
            $promoDiscount = $appliedPromo ? $appliedPromo->discountFor($subtotal) : 0.0;
        }

        return [
            'subtotal' => $subtotal,
            'bundle' => $bundle,
            'bundleDiscount' => $bundleDiscount,
            'promoDiscount' => $promoDiscount,
            'totalDiscount' => round($bundleDiscount + $promoDiscount, 2),
            'appliedPromo' => $appliedPromo,
            'promoIgnored' => $promoIgnored,
        ];
    }
}
