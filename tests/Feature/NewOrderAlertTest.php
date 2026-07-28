<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderCreated;
use App\Livewire\Admin\NewOrderAlert;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NewOrderAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_notification_service_dispatches_order_created_event(): void
    {
        Mail::fake();
        Event::fake([OrderCreated::class]);

        $order = Order::factory()->create();

        app(OrderNotificationService::class)->sendOrderCreated($order);

        Event::assertDispatched(OrderCreated::class, fn (OrderCreated $event): bool => $event->order->id === $order->id);
    }

    public function test_order_created_event_broadcasts_to_admin_orders_channel(): void
    {
        $order = Order::factory()->create();
        $event = new OrderCreated($order);

        $this->assertSame('order.created', $event->broadcastAs());
        $this->assertSame(['orderId' => $order->id], $event->broadcastWith());
        $this->assertSame('private-admin.orders', $event->broadcastOn()[0]->name);
    }

    public function test_admin_orders_channel_allows_only_administrators(): void
    {
        $admin = User::factory()->administrator()->create();
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($admin)
            ->post('/broadcasting/auth', [
                'socket_id' => '1.1',
                'channel_name' => 'private-admin.orders',
            ])
            ->assertOk();

        $this->actingAs($customer)
            ->post('/broadcasting/auth', [
                'socket_id' => '1.1',
                'channel_name' => 'private-admin.orders',
            ])
            ->assertForbidden();
    }

    public function test_new_order_alert_does_not_load_orders_for_guests(): void
    {
        Order::factory()->create(['status' => OrderStatus::New]);

        Livewire::test(NewOrderAlert::class)
            ->assertSet('currentOrder', null)
            ->assertSet('queue', []);
    }

    public function test_new_order_alert_does_not_load_orders_for_non_administrators(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        Order::factory()->create(['status' => OrderStatus::New]);

        Livewire::actingAs($customer)
            ->test(NewOrderAlert::class)
            ->assertSet('currentOrder', null)
            ->assertSet('queue', []);
    }

    public function test_admin_panel_is_available_at_custom_path(): void
    {
        $this->get('/admin/user/login/login')
            ->assertOk();

        $this->get('/admin')
            ->assertNotFound();

        $this->get('/admin/login')
            ->assertNotFound();
    }

    public function test_new_order_alert_loads_pending_orders_on_mount(): void
    {
        $admin = User::factory()->administrator()->create();
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        Livewire::actingAs($admin)
            ->test(NewOrderAlert::class)
            ->assertSet('currentOrder.id', $order->id)
            ->assertSet('queue', []);
    }

    public function test_new_order_alert_accept_updates_order_status(): void
    {
        $admin = User::factory()->administrator()->create();
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        Livewire::actingAs($admin)
            ->test(NewOrderAlert::class)
            ->call('acceptOrder')
            ->assertSet('currentOrder', null);

        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
    }

    public function test_new_order_alert_queues_incoming_broadcast_orders(): void
    {
        $admin = User::factory()->administrator()->create();
        $firstOrder = Order::factory()->create(['status' => OrderStatus::New]);
        $secondOrder = Order::factory()->create(['status' => OrderStatus::New]);

        Livewire::actingAs($admin)
            ->test(NewOrderAlert::class)
            ->assertSet('currentOrder.id', $firstOrder->id)
            ->call('handleNewOrder', $secondOrder->id)
            ->assertSet('queue', [$secondOrder->id]);
    }

    public function test_new_order_alert_polls_for_new_orders(): void
    {
        $admin = User::factory()->administrator()->create();
        $order = Order::factory()->create(['status' => OrderStatus::New]);

        Livewire::actingAs($admin)
            ->test(NewOrderAlert::class)
            ->set('currentOrder', null)
            ->set('queue', [])
            ->call('pollForNewOrders')
            ->assertSet('currentOrder.id', $order->id);
    }
}
