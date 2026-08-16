<?php

namespace App\Services\Meta;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Str;

class MetaEventFactory
{
    public function newEventId(): string
    {
        return (string) Str::uuid();
    }

    public function purchaseEventId(Order $order): string
    {
        return 'purchase_order_'.$order->id;
    }

    public function productContentId(int $productId): string
    {
        return 'product_'.$productId;
    }

    public function lunchContentId(int $lunchMenuItemId): string
    {
        return 'lunch_'.$lunchMenuItemId;
    }

    public function contentIdForCartItem(CartItem $item): ?string
    {
        if ($item->isLunchItem() && $item->lunch_menu_item_id) {
            return $this->lunchContentId((int) $item->lunch_menu_item_id);
        }

        if ($item->product_id) {
            return $this->productContentId((int) $item->product_id);
        }

        return null;
    }

    public function contentIdForOrderItem(OrderItem $item): ?string
    {
        if (is_string($item->meta_content_id) && $item->meta_content_id !== '') {
            return $item->meta_content_id;
        }

        if ($item->isLunchItem()) {
            return null;
        }

        if ($item->product_id) {
            return $this->productContentId((int) $item->product_id);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $customData
     * @param  array<string, string>  $userData
     * @return array<string, mixed>
     */
    public function makeEvent(
        string $eventName,
        string $eventId,
        string $eventSourceUrl,
        array $customData = [],
        array $userData = [],
        ?int $eventTime = null,
    ): array {
        $event = [
            'event_name' => $eventName,
            'event_time' => $eventTime ?? time(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl,
            'user_data' => $userData,
        ];

        if ($customData !== []) {
            $event['custom_data'] = $customData;
        }

        return $event;
    }

    /**
     * @return array<string, mixed>
     */
    public function viewContentCustomData(Product $product): array
    {
        $variant = $product->variants->first();
        $value = $variant
            ? (float) $variant->price
            : (float) $product->base_price;

        return [
            'content_ids' => [$this->productContentId((int) $product->id)],
            'content_type' => 'product',
            'content_name' => $product->name,
            'value' => $this->money($value),
            'currency' => $this->currency(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function addToCartCustomData(CartItem $item, int $addedQuantity): array
    {
        $contentId = $this->contentIdForCartItem($item);
        $unitPrice = $this->money((float) $item->unit_price);
        $quantity = max(1, $addedQuantity);

        $payload = [
            'content_type' => 'product',
            'contents' => [[
                'id' => $contentId,
                'quantity' => $quantity,
                'item_price' => $unitPrice,
            ]],
            'value' => $this->money($unitPrice * $quantity),
            'currency' => $this->currency(),
        ];

        if ($contentId !== null) {
            $payload['content_ids'] = [$contentId];
        }

        return $payload;
    }

    /**
     * @param  array{subtotal: float, totalDiscount: float}  $pricing
     * @return array<string, mixed>
     */
    public function initiateCheckoutCustomData(Cart $cart, array $pricing): array
    {
        $contents = [];
        $contentIds = [];

        foreach ($cart->items as $item) {
            $contentId = $this->contentIdForCartItem($item);

            if ($contentId === null) {
                continue;
            }

            $contentIds[] = $contentId;
            $contents[] = [
                'id' => $contentId,
                'quantity' => (int) $item->quantity,
                'item_price' => $this->money((float) $item->unit_price),
            ];
        }

        return [
            'content_ids' => array_values(array_unique($contentIds)),
            'content_type' => 'product',
            'contents' => $contents,
            'value' => $this->money(max(0, (float) $pricing['subtotal'] - (float) $pricing['totalDiscount'])),
            'currency' => $this->currency(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function purchaseCustomData(Order $order): array
    {
        $contents = [];
        $contentIds = [];

        foreach ($order->items as $item) {
            $contentId = $this->contentIdForOrderItem($item);

            if ($contentId === null) {
                continue;
            }

            $contentIds[] = $contentId;
            $contents[] = [
                'id' => $contentId,
                'quantity' => (int) $item->quantity,
                'item_price' => $this->money((float) $item->unit_price),
            ];
        }

        return [
            'content_ids' => array_values(array_unique($contentIds)),
            'content_type' => 'product',
            'contents' => $contents,
            'value' => $this->money((float) $order->total),
            'currency' => $this->currency(),
            'order_id' => $order->order_number,
        ];
    }

    public function money(float $value): float
    {
        return round($value, 2);
    }

    public function currency(): string
    {
        return (string) config('meta.currency', 'EUR');
    }
}
