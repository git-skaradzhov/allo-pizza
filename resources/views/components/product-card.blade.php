@props(['product'])

@php
    $image = $product->image ? product_image_url($product->image, product_image_tier_size('small')) : null;

    $variants = $product->relationLoaded('variants') ? $product->variants : collect();
    $prices = $variants->pluck('price')->filter()->map(fn ($p) => (float) $p);

    if ($prices->isNotEmpty()) {
        $min = $prices->min();
        $max = $prices->max();
        $priceLabel = $min === $max
            ? money($min)
            : money($min).' – '.money($max);
    } else {
        $priceLabel = 'от '.money((float) $product->base_price);
    }

    $categorySlug = $product->category?->slug;
    $fallbackIcon = match ($categorySlug) {
        'sandvici-i-pierlenki', 'parlenki' => '🫓',
        'salads' => '🥗',
        'drinks' => '🥤',
        'desserts' => '🍰',
        default => '🍕',
    };
@endphp

<div {{ $attributes->merge(['class' => 'group flex flex-col rounded-[1.35rem] bg-white p-3 shadow-soft transition hover:shadow-card sm:rounded-3xl sm:p-4']) }}>
    <a href="{{ route('product.show', $product->slug) }}" class="block">
        <div class="relative w-full">
            <div class="absolute left-2 top-2 z-10 flex max-w-full flex-wrap gap-1">
                @if ($product->is_new)
                    <span class="rounded-full bg-gold-500 px-1.5 py-0.5 text-[10px] font-bold text-brand-900 sm:px-2 sm:text-xs">Ново</span>
                @endif
                @if ($product->is_promo)
                    <span class="rounded-full bg-brand-500 px-1.5 py-0.5 text-[10px] font-bold text-white sm:px-2 sm:text-xs">Промо</span>
                @endif
                @if ($product->is_spicy)
                    <span class="rounded-full bg-white px-1.5 py-0.5 text-[10px] font-bold text-brand-600 shadow-sm ring-1 ring-stone-200 sm:px-2 sm:text-xs">🌶 Люто</span>
                @endif
            </div>

            <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-2xl border border-stone-100 bg-white sm:rounded-3xl">
                @if ($image)
                    <img src="{{ $image }}" alt="{{ $product->image_alt ?: $product->name }}" loading="lazy"
                         class="h-full w-full object-contain p-2 transition duration-300 group-hover:scale-105 sm:p-3">
                @else
                    <span class="text-5xl sm:text-6xl">{{ $fallbackIcon }}</span>
                @endif
            </div>
        </div>
    </a>

    <div class="mt-2.5 flex flex-1 flex-col sm:mt-3">
        <a href="{{ route('product.show', $product->slug) }}" class="block">
            <h3 class="text-[15px] font-extrabold leading-tight text-stone-900 group-hover:text-brand-600 sm:text-lg">{{ $product->name }}</h3>
        </a>
        <p class="mt-1 line-clamp-2 flex-1 text-xs leading-snug text-stone-500 sm:mt-1.5 sm:line-clamp-3 sm:text-sm">{{ $product->short_description }}</p>

        <div class="mt-3 flex flex-col gap-2 sm:mt-4 sm:flex-row sm:items-center sm:justify-between">
            <span class="text-xs font-bold leading-tight text-stone-900 sm:text-sm">{{ $priceLabel }}</span>
            <a href="{{ route('product.show', $product->slug) }}"
               class="inline-flex w-full items-center justify-center rounded-xl border-2 border-brand-500 px-3 py-1.5 text-xs font-bold text-brand-600 transition hover:bg-brand-500 hover:text-white sm:w-auto sm:px-4 sm:text-sm">
                Добави
            </a>
        </div>
    </div>
</div>
