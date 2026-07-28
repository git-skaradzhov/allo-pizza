@php
    $order = $this->getOrder();
@endphp

@if ($order)
    <x-filament-widgets::widget>
        <x-filament::section>
            @include('filament.partials.order-details', ['order' => $order])
        </x-filament::section>
    </x-filament-widgets::widget>
@endif
