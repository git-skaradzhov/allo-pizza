<?php

namespace Database\Factories;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 15, 80);
        $deliveryPrice = fake()->randomElement([0, 3.50]);
        $discount = 0;

        return [
            'order_number' => Order::generateOrderNumber(),
            'customer_id' => Customer::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('08########'),
            'delivery_type' => DeliveryType::Delivery,
            'delivery_address' => fake()->streetAddress().', София',
            'delivery_lat' => fake()->latitude(42.6, 42.8),
            'delivery_lng' => fake()->longitude(23.2, 23.4),
            'delivery_price' => $deliveryPrice,
            'delivery_quote_required' => false,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $subtotal + $deliveryPrice - $discount,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'status' => OrderStatus::New,
            'customer_note' => fake()->optional()->sentence(),
            'admin_note' => null,
        ];
    }

    public function pickup(): static
    {
        return $this->state(fn () => [
            'delivery_type' => DeliveryType::Pickup,
            'delivery_address' => null,
            'delivery_lat' => null,
            'delivery_lng' => null,
            'delivery_price' => 0,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::Completed,
        ]);
    }
}
