<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'delivery_type' => $this->delivery_type->value,
            'delivery_type_label' => $this->delivery_type->label(),
            'delivery_address' => $this->delivery_address,
            'delivery_lat' => $this->delivery_lat ? (float) $this->delivery_lat : null,
            'delivery_lng' => $this->delivery_lng ? (float) $this->delivery_lng : null,
            'delivery_price' => (float) $this->delivery_price,
            'delivery_quote_required' => (bool) $this->delivery_quote_required,
            'delivery_fee_label' => $this->deliveryFeeLabel(),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'payment_method' => $this->payment_method->value,
            'payment_method_label' => $this->payment_method->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'customer_note' => $this->customer_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'note' => $item->note,
            ])),
        ];
    }
}
