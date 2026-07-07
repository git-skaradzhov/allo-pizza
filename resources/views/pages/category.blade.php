@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forCategory($category);
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Меню', 'url' => route('menu')],
        ['label' => $category->name],
    ]" />

    <h1 class="mb-2 text-3xl font-extrabold tracking-tight">{{ $category->name }}</h1>
    @if ($category->description)
        <p class="mb-8 max-w-2xl text-stone-500">{{ $category->description }}</p>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
        @forelse ($category->products as $product)
            <x-product-card :product="$product" />
        @empty
            <p class="text-stone-500">Няма продукти в тази категория.</p>
        @endforelse
    </div>
@endsection
