<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Notifications\Notification;
use Livewire\Component;

class NewOrderAlert extends Component
{
    public ?Order $currentOrder = null;

    /** @var array<int> */
    public array $queue = [];

    public bool $soundBlocked = false;

    public function mount(): void
    {
        if (! auth()->user()?->isAdministrator()) {
            return;
        }

        $this->queue = $this->pendingOrderIds();

        if ($this->queue !== []) {
            $this->showNextOrder(playSound: true);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        if (blank(config('filament.broadcasting.echo.key'))) {
            return [];
        }

        return [
            'echo-private:admin.orders,.order.created' => 'handleNewOrder',
        ];
    }

    public function handleNewOrder(mixed $payload): void
    {
        $orderId = $this->normalizeOrderId($payload);

        if ($orderId === null) {
            return;
        }

        if (in_array($orderId, $this->knownOrderIds(), true)) {
            return;
        }

        $order = Order::query()->find($orderId);

        if (! $order || $order->status !== OrderStatus::New) {
            return;
        }

        $this->queue[] = $orderId;

        if ($this->currentOrder === null) {
            $this->showNextOrder(playSound: true);
        } else {
            $this->dispatch('new-order-sound');
        }
    }

    public function pollForNewOrders(): void
    {
        if (! auth()->user()?->isAdministrator()) {
            return;
        }

        $knownIds = $this->knownOrderIds();

        foreach ($this->pendingOrderIds() as $orderId) {
            if (! in_array($orderId, $knownIds, true)) {
                $this->handleNewOrder($orderId);
                $knownIds[] = $orderId;
            }
        }
    }

    public function acceptOrder(): void
    {
        if ($this->currentOrder === null) {
            return;
        }

        $order = Order::query()->find($this->currentOrder->id);

        if (! $order || $order->status !== OrderStatus::New) {
            $this->showNextOrder(playSound: true);

            return;
        }

        $order->update(['status' => OrderStatus::Accepted]);

        $this->dispatch('order-status-updated');

        Notification::make()
            ->title('Поръчката е приета')
            ->body("Поръчка {$order->order_number}")
            ->success()
            ->send();

        $this->showNextOrder(playSound: true);
    }

    public function enableSound(): void
    {
        $this->soundBlocked = false;
        $this->dispatch('new-order-sound');
    }

    protected function showNextOrder(bool $playSound): void
    {
        if ($this->queue === []) {
            $this->currentOrder = null;
            $this->dispatch('new-order-sound-stop');

            return;
        }

        $orderId = array_shift($this->queue);

        $this->currentOrder = Order::query()
            ->with(['items.options'])
            ->find($orderId);

        if ($this->currentOrder === null) {
            $this->showNextOrder($playSound);

            return;
        }

        if ($playSound) {
            $this->dispatch('new-order-sound');
        }
    }

    /**
     * @return array<int>
     */
    protected function pendingOrderIds(): array
    {
        return Order::query()
            ->where('status', OrderStatus::New)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @return array<int>
     */
    protected function knownOrderIds(): array
    {
        $ids = array_map(fn (mixed $id): int => (int) $id, $this->queue);

        if ($this->currentOrder !== null) {
            $ids[] = (int) $this->currentOrder->id;
        }

        return array_values(array_unique($ids));
    }

    protected function normalizeOrderId(mixed $payload): ?int
    {
        if (is_array($payload)) {
            $payload = $payload['orderId'] ?? $payload['id'] ?? null;
        }

        if (! is_numeric($payload)) {
            return null;
        }

        $orderId = (int) $payload;

        return $orderId > 0 ? $orderId : null;
    }

    public function render()
    {
        return view('livewire.admin.new-order-alert');
    }
}
