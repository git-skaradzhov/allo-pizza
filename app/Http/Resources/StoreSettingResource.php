<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'store_name' => $this->store_name,
            'store_phone' => $this->store_phone,
            'store_phone_secondary' => $this->store_phone_secondary,
            'store_email' => $this->store_email,
            'facebook_url' => $this->facebook_url,
            'instagram_url' => $this->instagram_url,
            'store_address' => $this->store_address,
            'store_lat' => $this->store_lat ? (float) $this->store_lat : null,
            'store_lng' => $this->store_lng ? (float) $this->store_lng : null,
            'delivery_zone_polygon' => $this->delivery_zone_polygon ?? [],
            'delivery_inside_price' => (float) $this->delivery_inside_price,
            'delivery_outside_price' => (float) $this->delivery_outside_price,
            'delivery_price' => (float) $this->delivery_price,
            'free_delivery_over' => $this->free_delivery_over ? (float) $this->free_delivery_over : null,
            'minimum_order_amount' => (float) $this->minimum_order_amount,
            'average_delivery_time' => $this->average_delivery_time,
            'is_store_open' => $this->is_store_open,
            'closed_message' => $this->closed_message,
        ];
    }
}
