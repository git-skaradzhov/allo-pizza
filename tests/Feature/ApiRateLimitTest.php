<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStoreData;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;
    use CreatesStoreData;

    public function test_api_orders_endpoint_is_rate_limited(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->createOpenStore();
        $product = $this->createProductWithVariant(15);
        $variant = $product->variants->first();

        $payload = [
            'customer_name' => 'Мария',
            'customer_phone' => '0899111222',
            'delivery_type' => 'pickup',
            'payment_method' => 'pay_at_store',
            'items' => [
                ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1],
            ],
        ];

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/orders', $payload)->assertStatus(201);
        }

        $this->postJson('/api/orders', $payload)->assertStatus(429);
    }

    public function test_api_login_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
