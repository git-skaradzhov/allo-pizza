@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Поръчка '.$order->order_number);
    $storeName = $settings->store_name ?: 'Allo! Pizza';
@endphp

@section('content')
    <x-breadcrumbs class="no-print" :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Поръчка '.$order->order_number],
    ]" />

    <div class="mx-auto max-w-2xl">
        <div class="no-print mb-6 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-brand-600">Поръчката е приета</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Благодарим!</h1>
            <p class="mt-2 text-stone-600">Номер на поръчката: <strong class="text-stone-900">{{ $order->order_number }}</strong></p>
        </div>

        <div class="no-print mb-6 flex flex-wrap justify-center gap-3">
            <button type="button"
                    data-print-receipt
                    class="inline-flex items-center gap-2 rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white shadow-soft transition hover:bg-brand-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5V4.875A1.125 1.125 0 017.875 3.75h8.25A1.125 1.125 0 0117.25 4.875V7.5M6 18.75h12A2.25 2.25 0 0020.25 16.5v-6A2.25 2.25 0 0018 8.25H6A2.25 2.25 0 003.75 10.5v6A2.25 2.25 0 006 18.75z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75v2.625c0 .621.504 1.125 1.125 1.125h5.25c.621 0 1.125-.504 1.125-1.125V18.75"/>
                </svg>
                Принтирай / Запази като PDF
            </button>
            <a href="{{ route('menu') }}"
               class="inline-flex items-center rounded-2xl border border-stone-200 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-600">
                Към менюто
            </a>
            @auth
                <a href="{{ route('account.orders.show', $order) }}"
                   class="inline-flex items-center rounded-2xl border border-stone-200 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-brand-300 hover:text-brand-600">
                    Моите поръчки
                </a>
            @endauth
        </div>

        <article class="print-receipt rounded-3xl border border-stone-200 bg-white p-6 sm:p-8">
            <header class="border-b border-stone-200 pb-5 text-center">
                <img src="{{ asset('images/logo-wide.png') }}" alt="{{ $storeName }}" class="mx-auto h-10 w-auto object-contain">
                <p class="mt-3 text-lg font-extrabold">{{ $storeName }}</p>
                @if ($settings->store_address)
                    <p class="mt-1 text-sm text-stone-500">{{ $settings->store_address }}</p>
                @endif
                @if ($settings->phoneNumbers())
                    <p class="mt-1 text-sm text-stone-500">{{ implode(' · ', $settings->phoneNumbers()) }}</p>
                @endif
                @if ($settings->store_email)
                    <p class="text-sm text-stone-500">{{ $settings->store_email }}</p>
                @endif
            </header>

            <div class="mt-5 grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-stone-500">Номер</p>
                    <p class="font-semibold">{{ $order->order_number }}</p>
                </div>
                <div>
                    <p class="text-stone-500">Дата</p>
                    <p class="font-semibold">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-stone-500">Клиент</p>
                    <p class="font-semibold">{{ $order->customer_name }}</p>
                </div>
                <div>
                    <p class="text-stone-500">Телефон</p>
                    <p class="font-semibold">{{ $order->customer_phone }}</p>
                </div>
                @if ($order->customer_email)
                    <div>
                        <p class="text-stone-500">Имейл</p>
                        <p class="font-semibold">{{ $order->customer_email }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-stone-500">Получаване</p>
                    <p class="font-semibold">{{ $order->delivery_type->label() }}</p>
                </div>
                <div>
                    <p class="text-stone-500">Плащане</p>
                    <p class="font-semibold">{{ $order->payment_method->label() }}</p>
                </div>
                @if ($order->delivery_address)
                    <div class="sm:col-span-2">
                        <p class="text-stone-500">Адрес</p>
                        <p class="font-semibold">{{ $order->delivery_address }}</p>
                    </div>
                @endif
                @if ($order->customer_note)
                    <div class="sm:col-span-2">
                        <p class="text-stone-500">Бележка</p>
                        <p class="font-semibold">{{ $order->customer_note }}</p>
                    </div>
                @endif
            </div>

            <h2 class="mt-6 text-lg font-bold">Продукти</h2>
            <div class="mt-3 space-y-3">
                @foreach ($order->items as $item)
                    <div class="flex justify-between gap-4 border-b border-stone-100 pb-3 text-sm last:border-0 last:pb-0">
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
                @if ((float) $order->discount > 0)
                    <div class="flex justify-between">
                        <span>Отстъпка@if ($order->promo_code) ({{ $order->promo_code }})@endif</span>
                        <span>−{{ money($order->discount) }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span>Доставка</span>
                    <span>{{ $order->deliveryFeeLabel() }}</span>
                </div>
                <div class="flex justify-between text-base font-extrabold">
                    <span>Общо</span>
                    <span class="text-brand-600">{{ money($order->total) }}</span>
                </div>
            </div>
        </article>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelector('[data-print-receipt]')?.addEventListener('click', function () {
            window.print();
        });
    </script>
@endpush
