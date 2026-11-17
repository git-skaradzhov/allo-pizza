<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderItemOptionType;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingOrdersPingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user?->isAdministrator()) {
            return $this->unauthenticated();
        }

        $request->session()->put('kitchen_heartbeat_at', now()->timestamp);

        $orders = Order::query()
            ->where('status', OrderStatus::New)
            ->with(['items.options'])
            ->orderBy('id')
            ->get();

        return $this->noStoreJson([
            'ok' => true,
            'orderIds' => $orders->map(fn (Order $order): int => (int) $order->id)->values()->all(),
            'orders' => $orders->map(fn (Order $order): array => $this->serializeOrder($order))->values()->all(),
            'csrf' => csrf_token(),
        ]);
    }

    public function accept(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();

        if (! $user?->isAdministrator()) {
            return $this->unauthenticated();
        }

        $request->session()->put('kitchen_heartbeat_at', now()->timestamp);

        if ($order->status !== OrderStatus::New) {
            return $this->noStoreJson([
                'ok' => true,
                'accepted' => false,
                'reason' => 'already_handled',
            ]);
        }

        $order->update(['status' => OrderStatus::Accepted]);

        return $this->noStoreJson([
            'ok' => true,
            'accepted' => true,
            'order_id' => (int) $order->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeOrder(Order $order): array
    {
        return [
            'id' => (int) $order->id,
            'order_number' => $order->order_number,
            'created_at' => $order->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i'),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'delivery_type' => $order->delivery_type->label(),
            'delivery_address' => $order->delivery_address,
            'payment_method' => $order->payment_method->label(),
            'customer_note' => $order->customer_note,
            'subtotal' => money($order->subtotal),
            'discount' => (float) $order->discount > 0 ? money($order->discount) : null,
            'promo_code' => $order->promo_code,
            'delivery_fee' => $order->deliveryFeeLabel(),
            'total' => money($order->total),
            'items' => $order->items->map(fn (OrderItem $item): array => [
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => (int) $item->quantity,
                'total' => money($item->total_price),
                'note' => $item->note,
                'options' => $item->options->map(fn (OrderItemOption $option): array => [
                    'name' => $option->name,
                    'removed' => $option->option_type === OrderItemOptionType::IngredientRemoved,
                    'extra' => $option->option_type === OrderItemOptionType::ExtraAdded,
                    'price' => (float) $option->price > 0 ? money($option->price) : null,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    protected function unauthenticated(): JsonResponse
    {
        return $this->noStoreJson([
            'ok' => false,
            'reason' => 'unauthenticated',
        ], 401);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function noStoreJson(array $payload, int $status = 200): JsonResponse
    {
        return response()
            ->json($payload, $status)
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
