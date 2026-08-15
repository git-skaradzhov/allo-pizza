<?php

namespace App\Http\Controllers\Api;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ApiOrderPricingService;
use App\Services\DeliveryService;
use App\Services\Meta\MetaEventFactory;
use App\Services\Meta\MetaPurchaseTracker;
use App\Services\OrderNotificationService;
use App\Services\OrderPlacementValidator;
use App\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        protected DeliveryService $deliveryService,
        protected StoreService $storeService,
        protected ApiOrderPricingService $apiOrderPricingService,
        protected OrderNotificationService $orderNotificationService,
        protected OrderPlacementValidator $orderPlacementValidator,
        protected MetaPurchaseTracker $metaPurchaseTracker,
        protected MetaEventFactory $metaEventFactory,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if (! $this->storeService->isOpen()) {
            return response()->json(['message' => 'В момента не приемаме поръчки.'], 422);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'delivery_type' => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['required_if:delivery_type,delivery', 'nullable', 'string'],
            'delivery_lat' => ['required_if:delivery_type,delivery', 'nullable', 'numeric', 'between:-90,90'],
            'delivery_lng' => ['required_if:delivery_type,delivery', 'nullable', 'numeric', 'between:-180,180'],
            'payment_method' => ['required', 'in:cash_on_delivery,pay_at_store'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        $subtotal = 0;
        $orderItems = [];
        $pricingLines = [];

        foreach ($validated['items'] as $itemData) {
            $product = Product::query()
                ->where('is_active', true)
                ->with('category')
                ->findOrFail($itemData['product_id']);

            if (! $product->category?->is_active) {
                abort(422, 'Продуктът не е наличен.');
            }

            $variant = ProductVariant::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->findOrFail($itemData['product_variant_id']);

            $unitPrice = (float) $variant->price;
            $lineTotal = $unitPrice * $itemData['quantity'];
            $subtotal += $lineTotal;

            $pricingLines[] = [
                'category_id' => $product->category_id,
                'category_slug' => $product->category?->slug,
                'diameter' => (int) ($variant->diameter ?? 0),
                'unit_price' => $unitPrice,
                'quantity' => $itemData['quantity'],
                'product_name' => $product->name,
                'options' => [],
                'is_product' => true,
            ];

            $orderItems[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $itemData['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $lineTotal,
                'note' => $itemData['note'] ?? null,
            ];
        }

        $pricing = $this->apiOrderPricingService->summarize(
            $subtotal,
            $pricingLines,
            $validated['promo_code'] ?? null,
        );

        if ($pricing['promoInvalid']) {
            return response()->json(['message' => 'Невалиден промо код.'], 422);
        }

        $deliveryType = DeliveryType::from($validated['delivery_type']);
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
            $deliveryType,
            $deliveryLat,
            $deliveryLng,
            $deliveryPrice,
            $deliveryQuoteRequired,
            $subtotal,
            $discount,
            $appliedPromo,
            $orderItems,
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
                'payment_method' => PaymentMethod::from($validated['payment_method']),
                'status' => OrderStatus::New,
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            if ($appliedPromo) {
                $appliedPromo->increment('used_count');
            }

            foreach ($orderItems as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'meta_content_id' => $this->metaEventFactory->productContentId((int) $item['product']->id),
                    'product_name' => $item['product']->name,
                    'variant_name' => $item['variant']->name.' '.$item['variant']->size_label,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'note' => $item['note'],
                ]);
            }

            return $order;
        });

        $this->orderNotificationService->sendOrderCreated($order);
        $this->metaPurchaseTracker->trackPurchaseAfterCommit($order, flashBrowserEvent: false);

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($request->user()?->customer?->id === $order->customer_id, 403);

        $order->load('items');

        return new OrderResource($order);
    }

    public function myOrders(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->where('customer_id', $request->user()->customer?->id)
            ->latest()
            ->with('items')
            ->paginate(15);

        return OrderResource::collection($orders);
    }
}
