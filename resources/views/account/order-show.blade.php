@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Поръчка '.$order->order_number);
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Профил', 'url' => route('account.index')],
        ['label' => 'Поръчки', 'url' => route('account.orders')],
        ['label' => $order->order_number],
    ]" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Поръчка {{ $order->order_number }}</h1>
        <a href="{{ route('account.orders') }}" class="text-sm text-brand-600 hover:underline">Всички поръчки</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-stone-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Детайли</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-stone-500">Статус</dt>
                    <dd>{{ $order->status->label() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-500">Дата</dt>
                    <dd>{{ $order->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-500">Получаване</dt>
                    <dd>{{ $order->delivery_type->label() }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-stone-500">Плащане</dt>
                    <dd>{{ $order->payment_method->label() }}</dd>
                </div>
                @if ($order->delivery_address)
                    <div>
                        <dt class="text-stone-500">Адрес</dt>
                        <dd class="mt-1">{{ $order->delivery_address }}</dd>
                    </div>
                @endif
            </dl>

            <form method="POST" action="{{ route('account.orders.reorder', $order) }}" class="mt-6">
                @csrf
                <button type="submit" class="rounded-lg border border-red-700 px-4 py-2 text-sm font-medium text-brand-600 hover:bg-brand-50">
                    Поръчай отново
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-stone-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-semibold">Продукти</h2>
            <div class="space-y-4">
                @foreach ($order->items as $item)
                    <div class="flex justify-between gap-4 text-sm border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                        <div>
                            <p class="font-medium">{{ $item->product_name }}</p>
                            @if ($item->variant_name)
                                <p class="text-stone-500">{{ $item->variant_name }} × {{ $item->quantity }}</p>
                            @else
                                <p class="text-stone-500">× {{ $item->quantity }}</p>
                            @endif
                            <x-order-item-options :options="$item->options" />
                            @if ($item->note)
                                <p class="mt-0.5 text-xs italic text-stone-400">„{{ $item->note }}"</p>
                            @endif
                        </div>
                        <span class="shrink-0 font-medium">{{ money($item->total_price) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                <div class="flex justify-between">
                    <span>Междинна сума</span>
                    <span>{{ money($order->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Доставка</span>
                    <span>{{ $order->deliveryFeeLabel() }}</span>
                </div>
                <div class="flex justify-between font-semibold">
                    <span>Общо</span>
                    <span>{{ money($order->total) }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

