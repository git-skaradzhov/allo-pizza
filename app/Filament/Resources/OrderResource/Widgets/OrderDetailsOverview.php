<?php

namespace App\Filament\Resources\OrderResource\Widgets;

use App\Models\Order;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class OrderDetailsOverview extends Widget
{
    protected static string $view = 'filament.widgets.order-details-overview';

    protected int | string | array $columnSpan = 'full';

    public ?Model $record = null;

    public function getOrder(): ?Order
    {
        if (! $this->record instanceof Order) {
            return null;
        }

        $this->record->loadMissing(['items.options']);

        return $this->record;
    }
}
