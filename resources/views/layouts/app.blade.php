<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo-meta
        :title="$seoTitle ?? config('app.name', 'Allo! Pizza')"
        :description="$seoDescription ?? null"
        :image="$seoImage ?? null"
        :canonical="$seoCanonical ?? null"
    />

    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-stone-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-3 py-2.5 sm:gap-4 sm:px-4 sm:py-3">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
                <img src="{{ asset('images/logo-wide.png') }}" alt="{{ $storeSetting->store_name ?? 'Allo! Pizza' }}" class="h-9 w-auto max-w-[148px] object-contain sm:h-10 sm:max-w-[180px] md:h-11 md:max-w-[220px]">
            </a>

            <div class="hidden flex-1 items-center gap-2 lg:flex">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-gold-500/15 px-3 py-1.5 text-sm font-semibold text-brand-700">
                    <span class="h-2 w-2 rounded-full {{ ($storeIsOpen ?? false) ? 'bg-green-500' : 'bg-stone-400' }}"></span>
                    {{ ($storeIsOpen ?? false) ? 'Отворено' : 'Затворено' }}
                </span>
                <span class="text-sm text-stone-500">Доставка до {{ $storeSetting->average_delivery_time ?? 30 }} мин.</span>
            </div>

            <nav class="ml-auto flex items-center gap-4 text-xs font-medium sm:gap-3 sm:text-sm lg:gap-3">
                <div class="hidden items-center gap-1 lg:flex lg:gap-3">
                    <a href="{{ route('menu') }}" class="rounded-lg px-2 py-2 text-stone-700 hover:bg-stone-100 sm:px-3">Меню</a>
                    <a href="{{ route('lunch.index') }}" class="rounded-lg px-2 py-2 text-stone-700 hover:bg-stone-100 sm:px-3">Обедно меню</a>

                    @if ($storeSetting?->phoneNumbers())
                        <div class="hidden items-center gap-2 xl:flex">
                            @foreach ($storeSetting->phoneNumbers() as $phone)
                                <a href="tel:{{ \App\Models\StoreSetting::normalizePhone($phone) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 font-bold text-brand-600 hover:bg-brand-50">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                                    {{ $phone }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @auth
                        <a href="{{ route('account.index') }}" class="rounded-lg px-2 py-2 text-stone-700 hover:bg-stone-100 sm:px-3">Профил</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="rounded-lg px-2 py-2 text-stone-700 hover:bg-stone-100 sm:px-3">
                                Изход
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-2 py-2 text-stone-700 hover:bg-stone-100 sm:px-3">Вход</a>
                    @endauth
                </div>

                <button
                    type="button"
                    id="mobile-menu-open"
                    class="inline-flex rounded-xl p-2.5 text-stone-700 transition hover:bg-stone-100 lg:hidden"
                    aria-label="Отвори менюто"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <a href="{{ route('cart') }}"
                   class="relative inline-flex items-center gap-2 rounded-xl bg-brand-500 px-3 py-2 font-semibold text-white shadow-soft transition hover:bg-brand-600 sm:px-4">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/>
                    </svg>
                    <span class="hidden sm:inline">Количка</span>
                    @if (($cartCount ?? 0) > 0)
                        <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-gold-500 px-1 text-xs font-bold text-brand-900">
                            {{ $cartCount }}
                        </span>
                    @endif
                </a>
            </nav>
        </div>
    </header>

    <x-mobile-menu />

    @if (session('status'))
        <div class="bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-800">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-brand-50 px-4 py-3 text-center text-sm font-medium text-brand-700">{{ session('error') }}</div>
    @endif

    @hasSection('full')
        @yield('full')
    @else
        <main class="mx-auto max-w-7xl px-3 py-6 sm:px-4 sm:py-8">
            @yield('content')
        </main>
    @endif

    <footer class="mt-16 border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-7xl px-3 py-8 sm:px-4 sm:py-10">
            <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
                <div class="max-w-sm">
                    <img src="{{ asset('images/logo-wide.png') }}" alt="{{ $storeSetting->store_name ?? 'Allo! Pizza' }}" class="h-14 w-auto max-w-[220px] object-contain">
                    <p class="mt-4 text-sm font-semibold text-stone-900">{{ $storeSetting->store_name ?? 'Allo! Pizza' }}</p>
                    <p class="mt-1 text-sm text-stone-500">
                        {{ $storeSetting->store_address ?? 'гр. Русе, ул. „Мария Луиза“, 22' }}
                    </p>
                    <x-store-contact-links class="mt-3" stacked link-class="text-base font-extrabold text-brand-600 hover:text-brand-700" />
                </div>

                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:gap-x-10">
                    <a href="{{ route('pages.show', 'za-nas') }}" class="text-stone-600 hover:text-brand-600">За нас</a>
                    <a href="{{ route('pages.show', 'dostavka') }}" class="text-stone-600 hover:text-brand-600">Доставка</a>
                    <a href="{{ route('pages.show', 'kontakti') }}" class="text-stone-600 hover:text-brand-600">Контакти</a>
                    <a href="{{ route('pages.show', 'obshti-usloviya') }}" class="text-stone-600 hover:text-brand-600">Общи условия</a>
                    <a href="{{ route('pages.show', 'politika-za-poveritelnost') }}" class="text-stone-600 hover:text-brand-600">Поверителност</a>
                    <a href="{{ route('menu') }}" class="text-stone-600 hover:text-brand-600">Меню</a>
                    <a href="{{ route('lunch.index') }}" class="text-stone-600 hover:text-brand-600">Обедно меню</a>
                </div>
            </div>

            <p class="mt-8 border-t border-stone-100 pt-6 text-xs text-stone-400">
                &copy; {{ date('Y') }} {{ $storeSetting->store_name ?? 'Allo! Pizza' }}. Всички права запазени.
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
