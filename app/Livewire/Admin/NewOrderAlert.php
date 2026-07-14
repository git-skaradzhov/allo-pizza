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

        $this->queue = Order::query()
            ->where('status', OrderStatus::New)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if ($this->queue !== []) {
            $this->showNextOrder(playSound: true);
        }
    }

    /**
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            'echo-private:admin.orders,.order.created' => 'handleNewOrder',
        ];
    }

    public function handleNewOrder(mixed $payload): void
    {
        $orderId = is_array($payload) ? ($payload['orderId'] ?? null) : $payload;

        if (! is_int($orderId)) {
            return;
        }

        if (in_array($orderId, $this->queue, true)) {
            return;
        }

        if ($this->currentOrder?->id === $orderId) {
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

        $knownIds = $this->queue;

        if ($this->currentOrder !== null) {
            $knownIds[] = $this->currentOrder->id;
        }

        $pendingIds = Order::query()
            ->where('status', OrderStatus::New)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($pendingIds as $orderId) {
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

    public function render()
    {
        return view('livewire.admin.new-order-alert');
    }
}
