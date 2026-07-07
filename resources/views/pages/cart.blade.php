@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;
    use Illuminate\Support\Facades\Storage;

    $seo = app(SeoBuilder::class)->forPrivatePage('Количка');
    $belowMinimum = $subtotal > 0 && $subtotal < (float) $settings->minimum_order_amount;
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Количка'],
    ]" />

    <h1 class="mb-6 text-3xl font-extrabold tracking-tight">Количка</h1>

    @if ($cart->items->isEmpty())
        <div class="rounded-3xl border border-stone-200 bg-white p-12 text-center">
            <div class="text-6xl">🛒</div>
            <p class="mt-4 text-stone-600">Количката ви е празна.</p>
            <a href="{{ route('menu') }}" class="mt-4 inline-block rounded-xl bg-brand-500 px-6 py-3 font-bold text-white hover:bg-brand-600">Към менюто</a>
        </div>
    @else
        <div class="grid gap-8 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                @foreach ($cart->items as $item)
                    @php
                        $itemImage = $item->isLunchItem()
                            ? ($item->item_image ? Storage::url($item->item_image) : null)
                            : ($item->product?->image ? product_image_url($item->product->image, product_image_tier_size('small')) : null);
                        $itemIcon = $item->isLunchItem() ? '🍽️' : '🍕';
                    @endphp
                    <div class="flex gap-4 rounded-3xl border border-stone-200 bg-white p-4">
                        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-stone-100 {{ $itemImage ? 'bg-white' : 'bg-gradient-to-br from-gold-300/40 to-brand-100' }} text-3xl">
                            @if ($itemImage)
                                <img src="{{ $itemImage }}" alt="{{ $item->displayName() }}" class="h-full w-full object-contain p-1">
                            @else
                                {{ $itemIcon }}
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h2 class="font-bold">{{ $item->displayName() }}</h2>
                                    @if ($item->isLunchItem())
                                        <p class="text-sm text-brand-600">Обедно меню</p>
                                        @if ($item->displayDescription())
                                            <p class="text-sm text-stone-500">{{ $item->displayDescription() }}</p>
                                        @endif
                                    @else
                                        @if ($item->variant)
                                            <p class="text-sm text-stone-500">{{ $item->variant->name }} ({{ $item->variant->size_label }})</p>
                                        @endif
                                    @endif
                                    <x-order-item-options :options="$item->options ?? []" />
                                    @if ($item->note)
                                        <p class="mt-1 text-xs italic text-stone-400">„{{ $item->note }}"</p>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('cart.items.remove', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-stone-400 hover:text-brand-600" title="Премахни">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            </div>

                            <div class="mt-auto flex items-center justify-between pt-3">
                                <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center rounded-xl border border-stone-300">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" name="quantity" value="{{ $item->quantity - 1 }}" class="px-3 py-1.5 font-bold text-stone-500 hover:text-brand-600">−</button>
                                    <span class="w-8 text-center text-sm font-bold">{{ $item->quantity }}</span>
                                    <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}" class="px-3 py-1.5 font-bold text-stone-500 hover:text-brand-600">+</button>
                                </form>

                                <span class="font-bold text-stone-900">{{ money($item->total_price) }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="h-fit rounded-3xl border border-stone-200 bg-white p-6">
                <h2 class="mb-4 text-lg font-bold">Обобщение</h2>

                <div class="mb-4">
                    @if ($appliedPromo)
                        <div class="flex items-center justify-between rounded-xl bg-green-50 px-3 py-2 text-sm">
                            <span class="font-semibold text-green-800">Код „{{ $appliedPromo->code }}" приложен</span>
                            <form method="POST" action="{{ route('cart.promo.remove') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-green-700 underline hover:text-green-900">премахни</button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('cart.promo.apply') }}" class="flex gap-2">
                            @csrf
                            <input type="text" name="code" placeholder="Промо код" class="w-full rounded-xl border-stone-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                            <button type="submit" class="shrink-0 rounded-xl bg-stone-800 px-4 text-sm font-semibold text-white hover:bg-stone-900">Приложи</button>
                        </form>
                    @endif
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-stone-500">Междинна сума</span>
                        <span class="font-semibold">{{ money($subtotal) }}</span>
                    </div>
                    @if ($discount > 0)
                        <div class="flex justify-between text-green-700">
                            <span>Отстъпка</span>
                            <span class="font-semibold">−{{ money($discount) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-stone-500">Доставка</span>
                        <span class="font-semibold">{{ $deliveryPrice > 0 ? money($deliveryPrice) : 'Безплатна' }}</span>
                    </div>
                    <p class="text-xs text-stone-400">
                        {{ money($settings->delivery_inside_price) }} в района · {{ money($settings->delivery_outside_price) }} извън района
                    </p>
                    @if ($settings->free_delivery_over)
                        <p class="text-xs text-stone-400">Безплатна доставка над {{ money($settings->free_delivery_over) }}</p>
                    @endif
                </div>

                <div class="mt-4 flex justify-between border-t border-stone-200 pt-4 text-lg font-extrabold">
                    <span>Общо</span>
                    <span class="text-brand-600">{{ money(max(0, $subtotal - $discount) + $deliveryPrice) }}</span>
                </div>

                @if ($belowMinimum)
                    <p class="mt-4 rounded-xl bg-gold-500/10 px-3 py-2 text-sm text-brand-700">
                        Минимална поръчка: {{ money($settings->minimum_order_amount) }}.
                        Добавете още {{ money($settings->minimum_order_amount - $subtotal) }}.
                    </p>
                @endif

                <a href="{{ route('checkout') }}"
                   class="mt-5 block rounded-2xl px-4 py-4 text-center text-lg font-bold text-white transition {{ $belowMinimum ? 'pointer-events-none bg-stone-300' : 'bg-brand-500 hover:bg-brand-600' }}">
                    Към плащане
                </a>
            </div>
        </div>
    @endif
@endsection
