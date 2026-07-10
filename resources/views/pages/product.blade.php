@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forProduct($product);
    $galleryPaths = collect([$product->image])
        ->merge($product->images->pluck('image'))
        ->filter()
        ->unique()
        ->values();
    $galleryDisplayUrls = $galleryPaths->map(fn ($path) => product_image_url($path, 650));
    $firstVariant = $product->variants->first();
    $firstPrice = $firstVariant->price ?? $product->base_price;
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => $product->category->name, 'url' => route('category.show', $product->category->slug)],
        ['label' => $product->name],
    ]" />

    <form method="POST" action="{{ route('cart.add') }}" class="grid gap-6 lg:grid-cols-2 lg:gap-12" id="product-form">
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">

        <div class="flex items-start justify-center">
            @if ($galleryDisplayUrls->isNotEmpty())
                <div id="product-gallery" data-product-gallery class="w-full max-w-md space-y-3">
                    <button type="button"
                            data-gallery-open
                            aria-label="Увеличи снимката"
                            class="group relative flex aspect-square w-full cursor-zoom-in items-center justify-center overflow-hidden rounded-3xl border border-stone-100 bg-white">
                        <img data-gallery-main-image
                             src="{{ $galleryDisplayUrls->first() }}"
                             alt="{{ $product->image_alt ?: $product->name }}"
                             class="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-[1.02] sm:p-6">
                        <span class="pointer-events-none absolute bottom-3 right-3 flex h-9 w-9 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14zM11 8v6M8 11h6"/>
                            </svg>
                        </span>
                    </button>

                    @foreach ($galleryPaths as $path)
                        <a href="{{ product_image_url($path, 1024) }}"
                           data-gallery-display-url="{{ product_image_url($path, 650) }}"
                           data-pswp-item
                           class="hidden"
                           aria-hidden="true"></a>
                    @endforeach

                    @if ($galleryDisplayUrls->count() > 1)
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            @foreach ($galleryDisplayUrls as $index => $url)
                                <button type="button"
                                        data-gallery-thumb="{{ $index }}"
                                        aria-label="Снимка {{ $index + 1 }}"
                                        aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                                        class="{{ $index === 0 ? 'border-brand-500 ring-2 ring-brand-500/30' : 'border-stone-200' }} flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-white p-1 transition hover:border-brand-300">
                                    <img src="{{ $url }}" alt="" class="h-full w-full object-contain" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="flex aspect-square w-full max-w-md items-center justify-center overflow-hidden rounded-3xl border border-stone-100 bg-white">
                    <span class="text-7xl sm:text-[8rem]">🍕</span>
                </div>
            @endif
        </div>

        <div>
            <div class="flex flex-wrap gap-2">
                @if ($product->is_new)
                    <x-product-new-icon aria-hidden="true" />
                @endif
                @if ($product->is_promo)
                    <span class="rounded-full bg-brand-500 px-2.5 py-0.5 text-xs font-bold text-white">Промо</span>
                @endif
                @if ($product->is_spicy)
                    <span class="rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-bold text-brand-600">🌶 Люто</span>
                @endif
            </div>

            <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $product->name }}</h1>
            <p class="mt-2 text-stone-500">{{ $product->short_description }}</p>

            @if ($product->ingredients->isNotEmpty())
                <p class="mt-3 text-sm text-stone-600">
                    <span class="font-semibold text-stone-400">Състав:</span>
                    {{ $product->ingredients->pluck('name')->join(', ') }}
                </p>
            @endif

            @if ($product->variants->isNotEmpty())
                <div class="mt-6">
                    <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-stone-400">Размер</h2>
                    <div class="grid grid-cols-3 gap-1.5 rounded-2xl bg-stone-100 p-1.5 sm:gap-2">
                        @foreach ($product->variants as $i => $variant)
                            <label class="cursor-pointer">
                                <input type="radio" name="product_variant_id" value="{{ $variant->id }}"
                                       data-price="{{ (float) $variant->price }}"
                                       class="peer sr-only variant-radio" {{ $i === 0 ? 'checked' : '' }}>
                                <span class="block rounded-xl px-2 py-2.5 text-center text-xs font-semibold text-stone-600 transition peer-checked:bg-white peer-checked:text-brand-600 peer-checked:shadow-soft sm:px-3 sm:text-sm">
                                    <span class="block">{{ $variant->name }}</span>
                                    <span class="block text-xs font-normal text-stone-400">{{ $variant->size_label }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($removableIngredients->isNotEmpty())
                <div class="mt-6">
                    <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-stone-400">Премахни съставки</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($removableIngredients as $ingredient)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="removed[]" value="{{ $ingredient->id }}" class="peer sr-only">
                                <span class="inline-flex items-center rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition peer-checked:border-brand-400 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-checked:line-through">
                                    {{ $ingredient->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($extraIngredients->isNotEmpty())
                <div class="mt-6">
                    <h2 class="mb-2 text-sm font-bold uppercase tracking-wide text-stone-400">Допълнителни добавки</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($extraIngredients as $ingredient)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="extras[]" value="{{ $ingredient->id }}"
                                       data-price="{{ (float) $ingredient->price }}"
                                       class="peer sr-only extra-checkbox">
                                <span class="inline-flex items-center gap-1 rounded-full border border-stone-300 px-3 py-1.5 text-sm text-stone-600 transition peer-checked:border-gold-500 peer-checked:bg-gold-500/10 peer-checked:text-brand-700">
                                    {{ $ingredient->name }}
                                    <span class="text-xs text-stone-400">+{{ money($ingredient->price) }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <label for="note" class="mb-2 block text-sm font-bold uppercase tracking-wide text-stone-400">Бележка</label>
                <input type="text" id="note" name="note" maxlength="500" placeholder="напр. без лук, разрязана..."
                       class="w-full rounded-xl border-stone-300 text-sm focus:border-brand-400 focus:ring-brand-400">
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                <div class="flex items-center rounded-xl border border-stone-300">
                    <button type="button" id="qty-minus" class="px-4 py-3 text-lg font-bold text-stone-500 hover:text-brand-600">−</button>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="20"
                           class="w-12 border-0 p-0 text-center text-lg font-bold focus:ring-0" readonly>
                    <button type="button" id="qty-plus" class="px-4 py-3 text-lg font-bold text-stone-500 hover:text-brand-600">+</button>
                </div>

                <button type="submit"
                        class="flex w-full flex-1 items-center justify-between gap-2 rounded-2xl bg-brand-500 px-5 py-4 text-base font-bold text-white shadow-soft transition hover:bg-brand-600 sm:text-lg">
                    <span>Добави в количката</span>
                    <span id="product-price">{{ money($firstPrice) }}</span>
                </button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('product-form');
                const priceEl = document.getElementById('product-price');
                const qtyInput = document.getElementById('quantity');

                function recalc() {
                    const variant = form.querySelector('.variant-radio:checked');
                    let unit = variant ? parseFloat(variant.dataset.price) : {{ (float) $firstPrice }};
                    form.querySelectorAll('.extra-checkbox:checked').forEach((c) => {
                        unit += parseFloat(c.dataset.price || 0);
                    });
                    const qty = parseInt(qtyInput.value || '1', 10);
                    priceEl.textContent = (unit * qty).toFixed(2) + ' €';
                }

                form.querySelectorAll('.variant-radio, .extra-checkbox').forEach((el) => {
                    el.addEventListener('change', recalc);
                });

                document.getElementById('qty-minus').addEventListener('click', () => {
                    qtyInput.value = Math.max(1, parseInt(qtyInput.value || '1', 10) - 1);
                    recalc();
                });
                document.getElementById('qty-plus').addEventListener('click', () => {
                    qtyInput.value = Math.min(20, parseInt(qtyInput.value || '1', 10) + 1);
                    recalc();
                });

                recalc();
            })();
        </script>
    @endpush
@endsection
