<?php

namespace App\Http\Controllers\Web;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CartPricingService;
use App\Services\CartService;
use App\Services\DeliveryService;
use App\Services\OrderNotificationService;
use App\Services\OrderPlacementValidator;
use App\Services\PromoService;
use App\Services\StoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CartPricingService $cartPricingService,
        protected DeliveryService $deliveryService,
        protected StoreService $storeService,
        protected PromoService $promoService,
        protected OrderNotificationService $orderNotificationService,
        protected OrderPlacementValidator $orderPlacementValidator,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart()->load(['items.product.category', 'items.variant']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Количката е празна.');
        }

        $settings = $this->storeService->settings();
        $pricing = $this->cartPricingService->summarize($cart);

        if ($pricing['subtotal'] < (float) $settings->minimum_order_amount) {
            return redirect()->route('cart')->with('error', 'Минималната стойност на поръчката не е достигната.');
        }

        return view('pages.checkout', [
            'cart' => $cart,
            'pricing' => $pricing,
            'subtotal' => $pricing['subtotal'],
            'settings' => $settings,
            'isOpen' => $this->storeService->isOpen(),
            'deliveryPrice' => $this->deliveryService->deliveryPrice($pricing['subtotal']),
            'deliveryInsidePrice' => (float) $settings->delivery_inside_price,
            'deliveryOutsidePrice' => (float) $settings->delivery_outside_price,
            'zonePolygon' => $this->deliveryService->zonePolygon(),
            'freeDeliveryOver' => $settings->free_delivery_over ? (float) $settings->free_delivery_over : null,
            'googleMapsKey' => config('services.google_maps.key'),
            'discount' => $pricing['totalDiscount'],
            'bundleDiscount' => $pricing['bundleDiscount'],
            'promoDiscount' => $pricing['promoDiscount'],
            'bundle' => $pricing['bundle'],
            'promoIgnored' => $pricing['promoIgnored'],
            'appliedPromo' => $pricing['appliedPromo'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = $this->cartService->getCart()->load(['items.product.category', 'items.variant']);

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Количката е празна.');
        }

        if (! $this->storeService->isOpen()) {
            return back()->with('error', 'В момента не приемаме поръчки.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['required_if:delivery_type,delivery', 'nullable', 'string'],
            'delivery_lat' => ['required_if:delivery_type,delivery', 'nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required_if:delivery_type,delivery', 'nullable', 'numeric', 'between:-180,180'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $pricing = $this->cartPricingService->summarize($cart);
        $subtotal = $pricing['subtotal'];
        $deliveryType = DeliveryType::from($validated['delivery_type']);
        $paymentMethod = $deliveryType === DeliveryType::Delivery
            ? PaymentMethod::CashOnDelivery
            : PaymentMethod::PayAtStore;

        $deliveryLat = isset($validated['delivery_lat']) ? (float) $validated['delivery_lat'] : null;
        $deliveryLng = isset($validated['delivery_lng']) ? (float) $validated['delivery_lng'] : null;

        $this->orderPlacementValidator->assertMinimumOrderAmount($subtotal);
        $this->orderPlacementValidator->assertDeliveryLocation($deliveryType, $deliveryLat, $deliveryLng);

        $deliveryPrice = $deliveryType === DeliveryType::Delivery
            ? $this->deliveryService->deliveryPrice($subtotal, $deliveryLat, $deliveryLng)
            : 0;

        $deliveryQuoteRequired = $deliveryType === DeliveryType::Delivery
            && $this->deliveryService->requiresDeliveryQuote($deliveryLat, $deliveryLng);

        $appliedPromo = $pricing['appliedPromo'];
        $discount = $pricing['totalDiscount'];

        $order = DB::transaction(function () use (
            $validated,
            $request,
            $cart,
            $deliveryType,
            $deliveryLat,
            $deliveryLng,
            $deliveryPrice,
            $deliveryQuoteRequired,
            $subtotal,
            $discount,
            $appliedPromo,
            $paymentMethod,
        ) {
            $order = Order::query()->create([
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $request->user()?->customer?->id,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'delivery_type' => $deliveryType,
                'delivery_address' => $validated['delivery_address'] ?? null,
                'delivery_lat' => $deliveryLat,
                'delivery_lng' => $deliveryLng,
                'delivery_price' => $deliveryPrice,
                'delivery_quote_required' => $deliveryQuoteRequired,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'promo_code' => $appliedPromo?->code,
                'total' => max(0, $subtotal - $discount) + $deliveryPrice,
                'payment_method' => $paymentMethod,
                'status' => OrderStatus::New,
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            if ($appliedPromo) {
                $appliedPromo->increment('used_count');
            }

            foreach ($cart->items as $item) {
                $orderItem = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'item_type' => $item->item_type,
                    'product_id' => $item->product_id,
                    'product_name' => $item->displayName(),
                    'variant_name' => $item->isLunchItem()
                        ? 'Обедно меню'
                        : ($item->variant
                            ? trim($item->variant->name.' '.($item->variant->size_label ?? ''))
                            : null),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'note' => $item->note,
                ]);

                foreach ($item->options ?? [] as $option) {
                    $quantity = (int) ($option['quantity'] ?? 1);
                    $unitPrice = (float) ($option['price'] ?? 0);
                    $name = $option['name'] ?? '';

                    if (($option['type'] ?? '') === 'extra_added' && $quantity > 1) {
                        $name .= ' ×'.$quantity;
                    }

                    $orderItem->options()->create([
                        'option_type' => $option['type'] ?? 'extra_added',
                        'name' => $name,
                        'price' => ($option['type'] ?? '') === 'extra_added'
                            ? $unitPrice * $quantity
                            : $unitPrice,
                    ]);
                }
            }

            return $order;
        });

        $this->cartService->clear();
        $this->promoService->clear();

        $this->orderNotificationService->sendOrderCreated($order);

        $message = 'Поръчката е приета успешно. Номер: '.$order->order_number;

        if (auth()->check()) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('status', $message);
        }

        return redirect()
            ->route('home')
            ->with('status', $message);
    }
}
