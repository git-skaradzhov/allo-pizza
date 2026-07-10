@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $pageHeading = 'Allo! Pizza – Поръчай пица онлайн';
    $seo = app(SeoBuilder::class)->forHome($homeInfoPage);
@endphp

@section('full')
    <div id="main-content" class="mx-auto max-w-7xl px-3 py-4 sm:px-4 sm:py-5">
        <h1 @class([
            'font-extrabold tracking-tight',
            'sr-only' => $heroBanners->isNotEmpty(),
            'mb-5 text-3xl' => $heroBanners->isEmpty(),
        ])>{{ $pageHeading }}</h1>
        @php
            $isHeroCarousel = $heroBanners->count() > 1;
        @endphp
        @if ($heroBanners->isNotEmpty())
            <section @class([
                'relative mb-5 w-full overflow-hidden rounded-[1.5rem] shadow-soft sm:rounded-[2rem]',
                'aspect-[21/8]' => true,
            ])
                     @if ($isHeroCarousel) data-hero-carousel data-hero-interval="5000" @endif>
                    @foreach ($heroBanners as $index => $hero)
                        @php
                            $heroImage = public_media_url($hero->image);
                        @endphp
                        <div @if ($isHeroCarousel)
                                 data-hero-slide
                                 class="absolute inset-0 overflow-hidden {{ $index === 0 ? 'is-active' : '' }}"
                                 aria-hidden="{{ $index === 0 ? 'false' : 'true' }}"
                             @else
                                 class="absolute inset-0 overflow-hidden"
                             @endif>
                            @if ($heroImage)
                                <img src="{{ $heroImage }}" alt="{{ $hero->title }}"
                                     class="hero-slide-image"
                                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                                     decoding="async">
                                @unless ($hero->image_only)
                                    <span class="absolute inset-0 z-10 bg-gradient-to-r from-black/40 via-black/15 to-transparent"></span>
                                    <span class="absolute inset-x-0 bottom-0 z-10 h-2/5 bg-gradient-to-t from-black/45 to-transparent"></span>
                                @endunless
                            @else
                                <span class="absolute inset-0 z-0 bg-gradient-to-br from-brand-600 via-brand-500 to-gold-500"></span>
                            @endif

                            @unless ($hero->image_only)
                                <div class="hero-slide-overlay absolute inset-0 z-20 flex flex-col justify-end p-5 sm:p-8">
                                    <div @if ($isHeroCarousel) class="hero-slide-content max-w-xl" @else class="max-w-xl" @endif>
                                        <p class="text-sm font-bold uppercase tracking-wide text-gold-300">Allo! Pizza · Русе</p>
                                        <p class="mt-1 text-2xl font-black tracking-tight text-white sm:text-4xl">{{ $hero->title }}</p>
                                        @if ($hero->subtitle)
                                            <p class="mt-2 max-w-lg text-sm text-white/90 sm:text-base">{{ $hero->subtitle }}</p>
                                        @endif
                                        @if ($hero->button_text)
                                            <a href="{{ $hero->button_url ?: route('menu') }}"
                                               class="mt-4 inline-flex w-fit rounded-full bg-white px-5 py-2.5 text-sm font-bold text-brand-700 shadow-soft transition hover:bg-gold-500 hover:text-brand-900">
                                                {{ $hero->button_text }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endunless
                        </div>
                    @endforeach

                @if ($isHeroCarousel)
                    @php
                        $nextPeekImage = public_media_url($heroBanners->get(1)?->image);
                    @endphp
                    <div class="hero-peek absolute inset-y-0 right-0 z-[15] hidden w-7 overflow-hidden sm:block {{ $nextPeekImage ? '' : 'hidden' }}"
                         data-hero-peek
                         aria-hidden="true">
                        <img src="{{ $nextPeekImage ?: '' }}"
                             alt=""
                             class="hero-peek-image h-full w-full object-cover"
                             draggable="false">
                    </div>

                    <button type="button"
                            class="absolute left-3 top-1/2 z-30 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-sm transition hover:bg-black/50 sm:flex sm:left-4"
                            data-hero-prev
                            aria-label="Предишен банер">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button type="button"
                            class="absolute right-3 top-1/2 z-30 hidden h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-white backdrop-blur-sm transition hover:bg-black/50 sm:flex sm:right-4"
                            data-hero-next
                            aria-label="Следващ банер">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <div class="hero-carousel-dots absolute bottom-3 left-1/2 z-30 flex -translate-x-1/2 items-center gap-2 rounded-full bg-black/45 px-3 py-2 backdrop-blur-sm sm:gap-3 sm:px-4 sm:py-2.5"
                         data-hero-dots
                         role="tablist"
                         aria-label="Hero банери">
                        <div class="flex items-center gap-2">
                            @foreach ($heroBanners as $index => $hero)
                                <button type="button"
                                        role="tab"
                                        aria-label="Банер {{ $index + 1 }}: {{ $hero->title }}"
                                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                        data-hero-dot="{{ $index }}"
                                        @if ($index === 0) class="is-active" @endif>
                                </button>
                            @endforeach
                        </div>
                        <span class="text-xs font-bold tabular-nums text-white/90" data-hero-counter>1 / {{ $heroBanners->count() }}</span>
                    </div>
                @endif
            </section>

            @if ($heroBanners->isNotEmpty())
                @push('styles')
                    <link rel="stylesheet" href="{{ asset('css/hero-carousel.css') }}">
                @endpush
            @endif

            @if ($isHeroCarousel)
                @push('scripts')
                    <script src="{{ asset('js/hero-carousel.js') }}" defer></script>
                @endpush
            @endif
        @endif

        {{-- Delivery information strip --}}
        <section class="mb-5 grid grid-cols-2 gap-2 rounded-[1.5rem] bg-white p-3 shadow-soft sm:gap-3 sm:rounded-[2rem] sm:p-4 md:grid-cols-4">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-gold-500/20 text-lg sm:h-11 sm:w-11 sm:text-xl">🚚</span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Доставка</p>
                    <p class="text-sm font-extrabold text-stone-900 sm:text-base">{{ $storeSetting->store_address ?? 'гр. Русе' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-lg sm:h-11 sm:w-11 sm:text-xl">⏱</span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Време</p>
                    <p class="text-sm font-extrabold text-stone-900 sm:text-base">до {{ $storeSetting->average_delivery_time ?? 30 }} мин.</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl bg-stone-100 text-lg sm:h-11 sm:w-11 sm:text-xl">🧾</span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Минимум</p>
                    <p class="text-sm font-extrabold text-stone-900 sm:text-base">{{ money((float) ($storeSetting->minimum_order_amount ?? 0)) }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl {{ $isOpen ? 'bg-green-100 text-green-700' : 'bg-stone-100 text-stone-700' }} text-lg sm:h-11 sm:w-11 sm:text-xl">●</span>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">Статус</p>
                    <p class="text-sm font-extrabold leading-tight {{ $isOpen ? 'text-green-700' : 'text-stone-600' }} sm:text-base">{{ $workingHoursMessage }}</p>
                </div>
            </div>
        </section>

        {{-- Dodo-like horizontal promo cards --}}
        @php
            $cardThemes = [
                [
                    'bg' => 'from-sky-200 to-indigo-200',
                    'text' => 'text-stone-950',
                    'overlay' => 'linear-gradient(to top, rgba(30, 58, 138, 0.92) 0%, rgba(67, 56, 202, 0.48) 42%, rgba(0, 0, 0, 0.06) 100%)',
                ],
                [
                    'bg' => 'from-orange-100 to-amber-200',
                    'text' => 'text-stone-950',
                    'overlay' => 'linear-gradient(to top, rgba(154, 52, 18, 0.9) 0%, rgba(217, 119, 6, 0.45) 42%, rgba(0, 0, 0, 0.06) 100%)',
                ],
                [
                    'bg' => 'from-brand-500 to-brand-700',
                    'text' => 'text-white',
                    'overlay' => 'linear-gradient(to top, rgba(94, 15, 17, 0.93) 0%, rgba(144, 25, 28, 0.5) 42%, rgba(0, 0, 0, 0.08) 100%)',
                ],
                [
                    'bg' => 'from-slate-600 to-slate-800',
                    'text' => 'text-white',
                    'overlay' => 'linear-gradient(to top, rgba(15, 23, 42, 0.92) 0%, rgba(51, 65, 85, 0.48) 42%, rgba(0, 0, 0, 0.06) 100%)',
                ],
                [
                    'bg' => 'from-gold-300 to-orange-300',
                    'text' => 'text-white',
                    'overlay' => 'linear-gradient(to top, rgba(120, 53, 15, 0.9) 0%, rgba(189, 143, 8, 0.45) 42%, rgba(0, 0, 0, 0.06) 100%)',
                ],
                [
                    'bg' => 'from-stone-800 to-black',
                    'text' => 'text-white',
                    'overlay' => 'linear-gradient(to top, rgba(0, 0, 0, 0.93) 0%, rgba(41, 37, 36, 0.5) 42%, rgba(0, 0, 0, 0.08) 100%)',
                ],
            ];
        @endphp

        <section class="mb-7" data-horizontal-slider>
                <div class="flex items-center sm:gap-3">
                    <button type="button"
                            class="hidden h-12 w-7 shrink-0 items-center justify-center text-brand-500 transition hover:text-brand-600 disabled:pointer-events-none disabled:opacity-0 sm:flex"
                            aria-label="Предишни промо банери"
                            data-slider-prev>
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <div class="flex cursor-grab snap-x snap-mandatory gap-3 overflow-x-auto pb-2 scroll-smooth [scrollbar-width:none] sm:gap-4 [&::-webkit-scrollbar]:hidden"
                             data-slider-track>
                            @if ($showWorkingHoursCard)
                                <x-working-hours-summary />
                            @endif
                            @foreach ($smallBanners as $index => $banner)
                                @php
                                    $bannerImage = public_media_url($banner->image);
                                    $theme = $cardThemes[$index % count($cardThemes)];
                                    $titleLower = mb_strtolower($banner->title);
                                    $bannerEmoji = match (true) {
                                        str_contains($titleLower, 'меню') => '🍕',
                                        str_contains($titleLower, 'доставка') && str_contains($titleLower, 'район') => '📍',
                                        str_contains($titleLower, 'доставка') => '🚚',
                                        str_contains($titleLower, '4 + 1') => '🎁',
                                        str_contains($titleLower, 'отстъпка') => '📱',
                                        str_contains($titleLower, 'обед') => '🍽️',
                                        str_contains($titleLower, 'нова пица') => '🍕',
                                        str_contains($titleLower, 'комбо') => '🎁',
                                        str_contains($titleLower, 'промо') => '🔥',
                                        str_contains($titleLower, 'пърлен') => '🫓',
                                        default => '🍕',
                                    };
                                @endphp
                                <a href="{{ $banner->button_url ?: route('menu') }}"
                                   draggable="false"
                                   style="width: 200px; max-width: 200px; height: 260px;"
                                   class="group relative flex shrink-0 snap-start overflow-hidden rounded-[1.35rem] p-4 shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:rounded-[1.65rem] {{ $bannerImage ? 'bg-stone-900' : 'bg-gradient-to-br '.$theme['bg'] }}">
                                    @if ($bannerImage)
                                        <img src="{{ $bannerImage }}" alt="{{ $banner->title }}" loading="lazy" draggable="false"
                                             style="filter: brightness(0.82);"
                                             class="absolute inset-0 z-0 h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                        <span class="absolute inset-0 z-10" style="background: {{ $theme['overlay'] }}"></span>
                                    @else
                                        <span class="absolute right-2 top-2 z-0 text-5xl font-black leading-none opacity-25 {{ $theme['text'] }} sm:text-6xl">
                                            {{ $bannerEmoji }}
                                        </span>
                                    @endif

                                    <span class="relative z-20 mt-auto block w-full">
                                        <span class="block text-xl font-black leading-[1.05] tracking-tight {{ $bannerImage ? 'text-white' : $theme['text'] }}">
                                            {{ $banner->title }}
                                        </span>
                                        @if ($banner->subtitle)
                                            <span class="mt-2 block line-clamp-5 text-[13px] font-semibold leading-snug {{ $bannerImage ? 'text-white/90' : ($theme['text'] === 'text-white' ? 'text-white/85' : 'text-stone-800/80') }}">
                                                {{ $banner->subtitle }}
                                            </span>
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <button type="button"
                            class="hidden h-12 w-7 shrink-0 items-center justify-center text-brand-500 transition hover:text-brand-600 disabled:pointer-events-none disabled:opacity-0 sm:flex"
                            aria-label="Следващи промо банери"
                            data-slider-next>
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>
            </section>

        {{-- Category navigation --}}
        @if ($menuCategories->isNotEmpty())
            <nav class="sticky top-[58px] z-30 mb-7 border-b border-stone-200 bg-stone-50/95 py-3 backdrop-blur sm:top-[68px]">
                <div class="flex cursor-grab gap-2 overflow-x-auto pb-1 scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                     data-slider-track>
                    @foreach ($menuCategories as $category)
                        <a href="#cat-{{ $category->slug }}"
                           class="shrink-0 rounded-full bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-soft transition hover:bg-brand-500 hover:text-white">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </nav>
        @endif

        {{-- Featured products --}}
        @if ($featuredProducts->isNotEmpty())
            <section class="mb-10">
                <div class="mb-5 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-brand-600">Избрано от нас</p>
                        <h2 class="text-2xl font-black tracking-tight text-stone-950 sm:text-3xl">Препоръчани продукти</h2>
                    </div>
                    <a href="{{ route('menu') }}" class="hidden rounded-full bg-white px-4 py-2 text-sm font-bold text-stone-700 shadow-soft transition hover:text-brand-600 sm:inline-flex">
                        Цялото меню
                    </a>
                </div>
                <div data-horizontal-slider>
                    <div class="flex items-center sm:gap-3">
                        <button type="button"
                                class="hidden h-12 w-7 shrink-0 items-center justify-center text-brand-500 transition hover:text-brand-600 disabled:pointer-events-none disabled:opacity-0 sm:flex"
                                aria-label="Предишни продукти"
                                data-slider-prev>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        <div class="min-w-0 flex-1">
                            <div class="flex cursor-grab snap-x snap-mandatory gap-3 overflow-x-auto pb-2 scroll-smooth [scrollbar-width:none] sm:gap-5 [&::-webkit-scrollbar]:hidden"
                                 data-slider-track>
                                @foreach ($featuredProducts as $product)
                                    <x-product-card :product="$product" class="w-[168px] shrink-0 snap-start sm:w-[260px]" />
                                @endforeach
                            </div>
                        </div>

                        <button type="button"
                                class="hidden h-12 w-7 shrink-0 items-center justify-center text-brand-500 transition hover:text-brand-600 disabled:pointer-events-none disabled:opacity-0 sm:flex"
                                aria-label="Следващи продукти"
                                data-slider-next>
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </section>
        @endif

        {{-- Lunch menu highlight --}}
        @if ($lunchMenu)
            <section class="mb-10 overflow-hidden rounded-[1.5rem] border border-gold-500/40 bg-gradient-to-br from-gold-500/20 via-white to-brand-50 p-4 sm:rounded-[2rem] sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-bold text-brand-600">Делнично предложение</p>
                        <h2 class="mt-1 text-2xl font-black tracking-tight text-stone-950 sm:text-3xl">{{ $lunchMenu->title }}</h2>
                        <p class="mt-2 max-w-2xl text-stone-700">{{ $lunchMenu->message }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex w-fit items-center rounded-full bg-white px-4 py-2 text-sm font-bold text-brand-600 shadow-soft">
                            {{ substr($lunchMenu->start_time, 0, 5) }} – {{ substr($lunchMenu->end_time, 0, 5) }} ч.
                        </span>
                        <a href="{{ route('lunch.index') }}" class="inline-flex rounded-full bg-brand-500 px-5 py-2 text-sm font-bold text-white shadow-soft hover:bg-brand-600">
                            Виж обедното меню
                        </a>
                    </div>
                </div>

                @php
                    $lunchTeaserItems = $lunchMenu->teaserItems();
                @endphp
                @if ($lunchTeaserItems->isNotEmpty())
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                        @foreach ($lunchTeaserItems as $item)
                            <a href="{{ route('lunch.index') }}" class="flex items-center gap-3 rounded-2xl bg-white/80 p-3 shadow-soft transition hover:bg-white">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-gold-300/40 to-brand-100 text-2xl">{{ $item->fallbackIcon() }}</span>
                                <span>
                                    <span class="block font-extrabold text-stone-900">{{ $item->name }}</span>
                                    <span class="text-sm text-stone-500">{{ money((float) $item->price) }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Editable information blocks --}}
        @if ($homeInfoPage)
            <section class="mb-10 grid gap-5 rounded-[1.5rem] bg-white p-4 shadow-soft sm:rounded-[2rem] sm:p-6 lg:grid-cols-[0.85fr_1.15fr] lg:p-8">
                <div>
                    <p class="text-sm font-bold text-brand-600">Защо Allo! Pizza</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-stone-950 sm:text-3xl">{{ $homeInfoPage->title }}</h2>
                    <p class="mt-3 text-sm text-stone-500 sm:text-base">Посетете ни на място или поръчайте онлайн с бърза доставка в Русе.</p>
                </div>
                <div class="home-info-content text-sm leading-7 text-stone-700 [&_a]:font-bold [&_a]:text-brand-600 [&_li]:mb-1 [&_p]:mb-3 [&_strong]:text-stone-950 [&_ul]:grid [&_ul]:gap-2">
                    {!! $homeInfoPage->content !!}
                </div>
            </section>
        @endif

        {{-- Menu by category --}}
        @forelse ($menuCategories as $category)
            <section id="cat-{{ $category->slug }}" class="mb-12 scroll-mt-36">
                <h2 class="mb-4 text-2xl font-extrabold tracking-tight text-stone-900 sm:mb-5">{{ $category->name }}</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-5 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($category->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @empty
            <p class="py-12 text-center text-stone-500">Менюто се обновява. Заповядайте отново скоро!</p>
        @endforelse
    </div>
@endsection
