@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forMenu();
@endphp

@section('full')
    <div id="main-content" class="mx-auto max-w-7xl px-3 py-5 sm:px-4 sm:py-6">
        <x-breadcrumbs :items="[
            ['label' => 'Начало', 'url' => route('home')],
            ['label' => 'Меню'],
        ]" />

        <h1 class="mb-5 text-3xl font-extrabold tracking-tight">Меню</h1>

        @if ($categories->isNotEmpty())
            <nav class="sticky top-[58px] z-30 mb-6 border-b border-stone-200 bg-stone-50/95 py-3 backdrop-blur sm:top-[68px]">
                <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($categories as $category)
                        <a href="#cat-{{ $category->slug }}"
                           class="shrink-0 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-soft transition hover:bg-brand-500 hover:text-white">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        @forelse ($categories as $category)
            <section id="cat-{{ $category->slug }}" class="mb-12 scroll-mt-36">
                <h2 class="mb-5 text-2xl font-extrabold tracking-tight text-stone-900">{{ $category->name }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($category->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @empty
            <p class="py-12 text-center text-stone-500">Няма налични продукти в момента.</p>
        @endforelse
    </div>
@endsection
