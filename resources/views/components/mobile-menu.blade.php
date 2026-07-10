@php
    use App\Models\StoreSetting;
    use App\Services\CartService;
    use App\Services\StoreService;

    $storeSetting = StoreSetting::current();
    $storeIsOpen = app(StoreService::class)->isOpen();
    $cartCount = app(CartService::class)->itemCount();

    $mapsUrl = ($storeSetting->store_lat && $storeSetting->store_lng)
        ? 'https://www.google.com/maps/search/?api=1&query=' . $storeSetting->store_lat . ',' . $storeSetting->store_lng
        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($storeSetting->store_address ?? '');
@endphp

<div id="mobile-menu" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div
        id="mobile-menu-backdrop"
        class="absolute inset-0 bg-black/60 opacity-0 transition-opacity duration-300"
    ></div>

    <div
        id="mobile-menu-panel"
        class="absolute inset-y-0 right-0 flex w-full max-w-sm translate-x-full flex-col bg-stone-900 text-white shadow-2xl transition-transform duration-300 ease-out"
        role="dialog"
        aria-modal="true"
        aria-label="Меню"
    >
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center" data-mobile-menu-close>
                <img src="{{ asset('images/logo-wide.png') }}" alt="{{ $storeSetting->store_name ?? 'Allo! Pizza' }}" class="h-9 w-auto max-w-[160px] object-contain">
            </a>
            <button
                type="button"
                id="mobile-menu-close"
                class="rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white"
                aria-label="Затвори менюто"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="border-b border-white/10 px-4 py-3">
            @auth
                <div class="flex gap-2">
                    <a href="{{ route('account.index') }}" class="flex-1 rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-white/10" data-mobile-menu-close>
                        Профил
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm font-semibold text-white/80 transition hover:bg-white/10 hover:text-white">
                            Изход
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('login') }}" class="block rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-white/10" data-mobile-menu-close>
                    Вход
                </a>
            @endauth
        </div>

        <div class="border-b border-white/10 px-4 py-4">
            <div class="flex flex-wrap items-center gap-2">
                <span @class([
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold',
                    'bg-green-500/20 text-green-400' => $storeIsOpen,
                    'bg-white/10 text-white/70' => ! $storeIsOpen,
                ])>
                    <span @class([
                        'h-2 w-2 rounded-full',
                        'bg-green-400' => $storeIsOpen,
                        'bg-stone-500' => ! $storeIsOpen,
                    ])></span>
                    {{ $storeIsOpen ? 'Отворено' : 'Затворено' }}
                </span>
                <span class="text-sm text-white/60">Доставка до {{ $storeSetting->average_delivery_time ?? 30 }} мин.</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-2">
            <p class="px-2 py-2 text-xs font-semibold uppercase tracking-wider text-white/40">Поръчка</p>
            <a href="{{ route('menu') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10" data-mobile-menu-close>
                <img src="{{ asset('images/icons/menu.png') }}" alt="" class="h-5 w-5 shrink-0 object-contain" width="20" height="20" aria-hidden="true">
                Меню
            </a>
            @if ($showNewMenuNav ?? false)
                <a href="{{ route('new-menu.index') }}" class="mx-1 flex items-center gap-3 rounded-xl border-2 border-brand-500 bg-white/10 px-3 py-3 text-base font-bold text-white transition hover:bg-white/15" data-mobile-menu-close>
                    <img src="{{ asset('images/icons/new.png') }}" alt="" class="h-6 w-6 shrink-0 object-contain" width="24" height="24" aria-hidden="true">
                    Нови
                </a>
            @endif
            @if ($showLunchMenuNav ?? false)
                <a href="{{ route('lunch.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10" data-mobile-menu-close>
                    <svg class="h-5 w-5 shrink-0 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Обедно меню
                </a>
            @endif
            <a href="{{ route('cart') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10" data-mobile-menu-close>
                <svg class="h-5 w-5 shrink-0 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l-1 12H6L5 9z"/>
                </svg>
                Количка
                @if (($cartCount ?? 0) > 0)
                    <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gold-500 px-1.5 text-xs font-bold text-brand-900">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>

            <p class="mt-4 px-2 py-2 text-xs font-semibold uppercase tracking-wider text-white/40">Информация</p>
            <a href="{{ route('pages.show', 'za-nas') }}" class="block rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10" data-mobile-menu-close>
                За нас
            </a>
            <a href="{{ route('pages.show', 'dostavka') }}" class="block rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10" data-mobile-menu-close>
                Доставка
            </a>

            <p class="mt-4 px-2 py-2 text-xs font-semibold uppercase tracking-wider text-white/40">Контакти</p>

            @if ($storeSetting?->phoneNumbers())
                <div class="rounded-xl px-3 py-2">
                    <p class="py-1 text-base font-medium text-white">Телефони</p>
                    <div class="mt-1 space-y-1 pl-1">
                        @foreach ($storeSetting->phoneNumbers() as $phone)
                            <a
                                href="tel:{{ StoreSetting::normalizePhone($phone) }}"
                                class="flex items-center gap-2 rounded-lg py-2 text-lg font-bold text-brand-400 transition hover:text-brand-300"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                {{ $phone }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <a href="{{ route('pages.show', 'kontakti') }}" class="block rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10" data-mobile-menu-close>
                Контакти
            </a>

            <div class="rounded-xl px-3 py-3">
                <p class="text-base font-medium text-white">Адрес</p>
                <p class="mt-1 text-sm leading-relaxed text-white/60">
                    {{ $storeSetting->store_address ?? 'гр. Русе, ул. „Мария Луиза“, 22' }}
                </p>
            </div>

            <a
                href="{{ $mapsUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2 rounded-xl px-3 py-3 text-base font-medium text-white transition hover:bg-white/10"
                data-mobile-menu-close
            >
                <svg class="h-5 w-5 shrink-0 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Карта в Google Maps
            </a>

            @if (! empty($storeSetting?->store_email))
                <a href="mailto:{{ $storeSetting->store_email }}" class="block rounded-xl px-3 py-3 text-sm text-white/60 transition hover:bg-white/10 hover:text-white">
                    {{ $storeSetting->store_email }}
                </a>
            @endif
        </nav>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function initMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const panel = document.getElementById('mobile-menu-panel');
        const backdrop = document.getElementById('mobile-menu-backdrop');
        const openButton = document.getElementById('mobile-menu-open');
        const closeButton = document.getElementById('mobile-menu-close');

        if (!menu || !panel || !backdrop || !openButton || !closeButton) {
            return;
        }

        if (openButton.dataset.mobileMenuInit === 'true') {
            return;
        }

        openButton.dataset.mobileMenuInit = 'true';

        let isOpen = false;

        const openMenu = () => {
            if (isOpen) {
                return;
            }

            isOpen = true;
            menu.classList.remove('hidden');
            menu.setAttribute('aria-hidden', 'false');
            openButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');

            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('translate-x-full');
            });
        };

        const closeMenu = () => {
            if (!isOpen) {
                return;
            }

            isOpen = false;
            menu.setAttribute('aria-hidden', 'true');
            openButton.setAttribute('aria-expanded', 'false');
            backdrop.classList.add('opacity-0');
            panel.classList.add('translate-x-full');
            document.body.classList.remove('overflow-hidden');

            window.setTimeout(() => {
                if (!isOpen) {
                    menu.classList.add('hidden');
                }
            }, 300);
        };

        openButton.addEventListener('click', openMenu);
        closeButton.addEventListener('click', closeMenu);
        backdrop.addEventListener('click', closeMenu);

        menu.querySelectorAll('[data-mobile-menu-close]').forEach((element) => {
            element.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen) {
                closeMenu();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileMenu);
    } else {
        initMobileMenu();
    }
})();
</script>
@endpush
