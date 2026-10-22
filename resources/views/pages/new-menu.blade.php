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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet">
    <style>
        .nm-feature-icon {
            font-family: 'Material Symbols Outlined';
            font-weight: normal;
            font-style: normal;
            font-size: 1.375rem;
            line-height: 1;
            letter-spacing: normal;
            text-transform: none;
            display: inline-block;
            white-space: nowrap;
            word-wrap: normal;
            direction: ltr;
            -webkit-font-feature-settings: 'liga';
            -webkit-font-smoothing: antialiased;
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
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
                                <span class="nm-feature-icon" aria-hidden="true">menu_book</span>
                            </span>
                            <span class="text-xs font-bold uppercase leading-tight tracking-wide text-stone-200">Нови<br>рецепти</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-brand-500/60 text-brand-500 shadow-[0_0_16px_rgba(235,28,34,0.25)]">
                                <span class="nm-feature-icon" aria-hidden="true">eco</span>
                            </span>
                            <span class="text-xs font-bold uppercase leading-tight tracking-wide text-stone-200">Пресни<br>съставки</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-brand-500/60 text-brand-500 shadow-[0_0_16px_rgba(235,28,34,0.25)]">
                                <span class="nm-feature-icon" aria-hidden="true">favorite</span>
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
                                <span class="nm-feature-icon text-[1.125rem]" aria-hidden="true">calendar_month</span>
                            </span>
                            <p class="text-sm leading-snug text-stone-300">Всеки месец<br><span class="font-bold text-white">нови предложения</span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-500/40 text-brand-500">
                                <span class="nm-feature-icon text-[1.125rem]" aria-hidden="true">stars</span>
                            </span>
                            <p class="text-sm leading-snug text-stone-300">Специално<br><span class="font-bold text-white">селектирани вкусове</span></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brand-500/40 text-brand-500">
                                <span class="nm-feature-icon text-[1.125rem]" aria-hidden="true">verified</span>
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
