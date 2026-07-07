@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Моите поръчки');
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Профил', 'url' => route('account.index')],
        ['label' => 'Поръчки'],
    ]" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Моите поръчки</h1>
        <a href="{{ route('account.index') }}" class="text-sm text-brand-600 hover:underline">Назад към профила</a>
    </div>

    <div class="space-y-4">
        @forelse ($orders as $order)
            <a href="{{ route('account.orders.show', $order) }}" class="block rounded-xl border border-stone-200 bg-white p-4 hover:border-brand-300">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="font-semibold">{{ $order->order_number }}</span>
                    <span class="rounded-full bg-stone-100 px-3 py-1 text-sm">{{ $order->status->label() }}</span>
                </div>
                <div class="mt-2 flex justify-between text-sm text-stone-600">
                    <span>{{ $order->created_at->format('d.m.Y H:i') }}</span>
                    <span class="font-medium text-stone-900">{{ money($order->total) }}</span>
                </div>
            </a>
        @empty
            <p class="text-stone-600">Нямате поръчки.</p>
        @endforelse
    </div>

    @if (method_exists($orders, 'links'))
        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
@endsection

