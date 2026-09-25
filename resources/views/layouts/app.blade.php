<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <x-seo-meta :seo="$seo ?? null" />
    <x-analytics
        :page-events="$metaPageEvents ?? []"
        :flash-events="$metaFlashEvents ?? []"
        :tracking-config="$metaTrackingConfig ?? null"
    />
    <x-structured-data :graphs="$structuredData ?? []" />

    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        @media print {
            .site-header,
            footer,
            #mobile-menu,
            #cookie-consent-banner,
            #cookie-consent-settings,
            .no-print {
                display: none !important;
            }

            body {
                background: #fff !important;
            }

            main {
                max-width: none !important;
                padding: 0 !important;
            }

            .print-receipt {
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-stone-50 font-sans text-stone-900 antialiased @yield('bodyClass')">
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-xl focus:bg-brand-500 focus:px-4 focus:py-2 focus:text-white">
        Към съдържанието
    </a>

    <header class="site-header sticky top-0 z-40 border-b border-stone-200 bg-white/95 backdrop-blur print:hidden">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-3 py-2.5 sm:gap-4 sm:px-4 sm:py-3">
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
                    <img src="{{ asset('images/logo-wide.png') }}" alt="{{ $storeSetting->store_name ?? 'Allo! Pizza' }}" class="h-9 w-auto max-w-[148px] object-contain sm:h-10 sm:max-w-[180px] md:h-11 md:max-w-[220px]">
                </a>

                @if ($storeSetting?->phoneNumbers())
                    <div class="flex min-w-0 flex-col gap-0.5 lg:hidden">
                        @foreach ($storeSetting->phoneNumbers() as $phone)
                            <a href="tel:{{ \App\Models\StoreSetting::normalizePhone($phone) }}"
                               class="inline-flex items-center gap-1 whitespace-nowrap text-xs font-bold leading-tight text-brand-600 transition hover:text-brand-700 sm:text-sm">
                                <svg class="h-3 w-3 shrink-0 sm:h-3.5 sm:w-3.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                {{ $phone }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="hidden flex-1 items-center lg:flex">
                <span class="whitespace-nowrap text-sm text-stone-500">
                    Доставка до {{ $storeSetting->average_delivery_time ?? 30 }} мин.
                    от {{ money((float) ($storeSetting->free_delivery_over ?: 30)) }}
                    <strong class="font-bold text-stone-700">БЕЗПЛАТНА</strong>
                </span>
            </div>

            <nav class="ml-auto flex items-center gap-2 text-xs font-medium sm:gap-3 sm:text-sm lg:gap-2">
                <div class="hidden items-center gap-1 lg:flex lg:gap-1.5">
                    <a href="{{ route('menu') }}"
                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-stone-700 transition hover:bg-stone-100 sm:px-3">
                        <img src="{{ asset('images/icons/menu.png') }}" alt="" class="h-5 w-5 shrink-0 object-contain" width="20" height="20" aria-hidden="true">
                        <span>Меню</span>
                    </a>

                    @if ($showNewMenuNav)
                        <a href="{{ route('new-menu.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-full border-2 border-brand-500 bg-white px-2.5 py-1.5 font-bold text-brand-700 shadow-sm transition hover:border-brand-600 hover:bg-brand-50 sm:px-3">
                            <img src="{{ asset('images/icons/new.png') }}" alt="" class="h-6 w-6 shrink-0 object-contain" width="24" height="24" aria-hidden="true">
                            <span>Нови</span>
                        </a>
                    @endif

                    @if ($showLunchMenuNav)
                        <a href="{{ route('lunch.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-stone-700 transition hover:bg-stone-100 sm:px-3">
                            <svg class="h-4 w-4 shrink-0 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Обедно</span>
                        </a>
                    @endif

                    @if ($storeSetting?->phoneNumbers())
                        <div class="flex flex-col gap-0.5 border-l border-stone-200 pl-3">
                            @foreach ($storeSetting->phoneNumbers() as $phone)
                                <a href="tel:{{ \App\Models\StoreSetting::normalizePhone($phone) }}"
                                   class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold leading-tight text-brand-600 transition hover:bg-brand-50">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                    </svg>
                                    {{ $phone }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @auth
                        <a href="{{ route('account.index') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-stone-700 transition hover:bg-stone-100 sm:px-3">
                            <svg class="h-4 w-4 shrink-0 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.125a7.125 7.125 0 0115 0v.375A1.125 1.125 0 0118.375 21h-12.75A1.125 1.125 0 014.5 19.5v-.375z"/>
                            </svg>
                            <span>Профил</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-stone-700 transition hover:bg-stone-100 sm:px-3">
                                <svg class="h-4 w-4 shrink-0 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9.75m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                <span>Изход</span>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-2 text-stone-700 transition hover:bg-stone-100 sm:px-3">
                            <svg class="h-4 w-4 shrink-0 text-stone-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0h3.75m0 0l-3-3m3 3l-3 3"/>
                            </svg>
                            <span>Вход</span>
                        </a>
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
        <div class="no-print bg-green-50 px-4 py-3 text-center text-sm font-medium text-green-800">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="no-print bg-brand-50 px-4 py-3 text-center text-sm font-medium text-brand-700">{{ session('error') }}</div>
    @endif

    @hasSection('full')
        @yield('full')
    @else
        <main id="main-content" class="mx-auto max-w-7xl px-3 py-6 sm:px-4 sm:py-8">
            @yield('content')
        </main>
    @endif

    <x-site-footer />

    @stack('scripts')

    <x-cookie-consent />

    @vite(['resources/js/cookie-consent.js'])
</body>
</html>
