@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $pageTitle = $page?->title ?? $highlight?->title ?? 'Ново в менюто';
    $seo = app(SeoBuilder::class)->forNewMenu($page, $highlight);
    $intro = $highlight?->description
        ?? ($page?->content ? strip_tags($page->content) : null)
        ?? 'Открий най-новите вкусни предложения, специално създадени за теб!';
@endphp

@section('bodyClass', 'nm-dark-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/new-menu-dark.css') }}">
@endpush

@section('full')
    <div id="main-content" class="new-menu-dark relative min-h-screen overflow-hidden pb-12">
        <div class="relative mx-auto max-w-7xl px-4 pb-10 pt-6 sm:px-6 sm:pb-14 sm:pt-8">
            <x-breadcrumbs
                variant="inverted"
                :items="[
                    ['label' => 'Начало', 'url' => route('home')],
                    ['label' => $pageTitle],
                ]"
                class="mb-6 sm:mb-10"
            />

            <header class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)] lg:items-center lg:gap-12">
                <div>
                    <h1 class="text-5xl font-black uppercase leading-[0.9] tracking-tight sm:text-6xl lg:text-7xl">
                        <span class="block text-white drop-shadow-[0_4px_18px_rgba(0,0,0,0.6)]">Ново</span>
                        <span class="new-menu-hero__brush" aria-hidden="true"></span>
                        <span class="block text-white drop-shadow-[0_4px_18px_rgba(0,0,0,0.6)]">в менюто</span>
                    </h1>
                </div>

                <div class="space-y-5">
                    <p class="text-base leading-relaxed text-stone-300 sm:text-lg">{{ $intro }}</p>

                    @if ($highlight?->message)
                        <p class="text-sm font-semibold text-brand-400 sm:text-base">{{ $highlight->message }}</p>
                    @endif

                    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-4 pt-1">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-brand-500/60 text-brand-500 shadow-[0_0_16px_rgba(235,28,34,0.25)]">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 23c-3.9 0-7-2.4-8.5-6C2.5 14.2 4 10.5 7 8.5 6.5 11 8 13.5 10.5 15c-.5-3 1.5-6 4.5-7.5C13 4.5 11.5 2 9 2c5 0 8 4.5 8 10 0 2.2-.7 4.2-2 5.8 1.5-.5 2.5-2 2.5-3.8 0-2.5-2-4.5-4.5-4.5.5 2-1 4-3 5 2.5-1 4-3.5 4-6 0-4-3.5-7-7.5-7C3.5 1 0 5 0 10c0 6.1 5.4 11 12 13z"/></svg>
                            </span>
                            <span class="text-xs font-bold uppercase leading-tight tracking-wide text-stone-200">Нови<br>рецепти</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-brand-500/60 text-brand-500 shadow-[0_0_16px_rgba(235,28,34,0.25)]">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2l2.9 6.26L20 9.27l-5 4.87 1.18 6.88L12 17.77l-4.18 3.25L9 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                            </span>
                            <span class="text-xs font-bold uppercase leading-tight tracking-wide text-stone-200">Пресни<br>съставки</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-brand-500/60 text-brand-500 shadow-[0_0_16px_rgba(235,28,34,0.25)]">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            </span>
                            <span class="text-xs font-bold uppercase leading-tight tracking-wide text-stone-200">Създадени<br>с любов</span>
                        </div>
                    </div>
                </div>
            </header>
        </div>

        <section class="relative mx-auto max-w-7xl px-4 sm:px-6">
            @if ($highlight && $highlight->products->isNotEmpty())
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($highlight->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <div class="new-menu-footer-bar mt-8 flex flex-col gap-6 rounded-2xl px-5 py-6 sm:mt-12 sm:flex-row sm:items-center sm:justify-between sm:gap-8 sm:px-8 sm:py-7">
                    <div class="grid flex-1 gap-5 sm:grid-cols-3 sm:gap-8">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-500/40 text-brand-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <p class="text-sm leading-snug text-stone-300">Всеки месец<br><span class="font-bold text-white">нови предложения</span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-500/40 text-brand-500">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.9 6.26L20 9.27l-5 4.87 1.18 6.88L12 17.77l-4.18 3.25L9 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>
                            </span>
                            <p class="text-sm leading-snug text-stone-300">Специално<br><span class="font-bold text-white">селектирани вкусове</span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-500/40 text-brand-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </span>
                            <p class="text-sm leading-snug text-stone-300">Пресни и<br><span class="font-bold text-white">качествени съставки</span></p>
                        </div>
                    </div>

                    <a href="{{ route('menu') }}"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border-2 border-brand-500 bg-transparent px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-brand-500/15">
                        Виж всички продукти
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-stone-700 bg-stone-900/50 px-6 py-16 text-center">
                    <img src="{{ asset('images/icons/new.png') }}" alt="" class="mx-auto h-16 w-16 object-contain opacity-60" width="64" height="64" aria-hidden="true">
                    <p class="mt-4 text-xl font-bold text-white">В момента няма нови предложения</p>
                    <p class="mt-2 text-stone-400">Разгледайте цялото ни меню с пици, пърленки и напитки.</p>
                    <a href="{{ route('menu') }}" class="mt-6 inline-flex rounded-lg bg-brand-500 px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-brand-600">
                        Към менюто
                    </a>
                </div>
            @endif
        </section>
    </div>
@endsection
