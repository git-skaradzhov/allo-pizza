<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_type',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'delivery_price',
        'delivery_quote_required',
        'subtotal',
        'discount',
        'promo_code',
        'total',
        'payment_method',
        'status',
        'customer_note',
        'admin_note',
        'meta_purchase_event_id',
        'meta_purchase_sent_at',
        'meta_purchase_attempts',
        'meta_purchase_last_error',
    ];

    protected function casts(): array
    {
        return [
            'delivery_type' => DeliveryType::class,
            'delivery_lat' => 'decimal:7',
            'delivery_lng' => 'decimal:7',
            'delivery_price' => 'decimal:2',
            'delivery_quote_required' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'meta_purchase_sent_at' => 'datetime',
            'meta_purchase_attempts' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function deliveryFeeLabel(): string
    {
        if ($this->delivery_quote_required) {
            return 'Уточнява се допълнително';
        }

        return money($this->delivery_price);
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
    }
}
