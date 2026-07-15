@php
    use App\Models\LunchMenu;
    use App\Models\NewMenuHighlight;
    use App\Models\StoreSetting;
    use App\Services\StoreService;

    $storeSetting = $storeSetting ?? StoreSetting::current();
    $storeService = app(StoreService::class);
    $storeIsOpen = $storeIsOpen ?? $storeService->isOpen();
    $workingHoursMessage = $workingHoursMessage ?? $storeService->workingHoursMessage();
    $showLunchMenuNav = $showLunchMenuNav ?? LunchMenu::isPublished();
    $showNewMenuNav = $showNewMenuNav ?? NewMenuHighlight::isPublished();

    $mapsUrl = ($storeSetting->store_lat && $storeSetting->store_lng)
        ? 'https://www.google.com/maps/search/?api=1&query=' . $storeSetting->store_lat . ',' . $storeSetting->store_lng
        : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($storeSetting->store_address ?? '');
@endphp

<footer class="mt-16 border-t border-stone-200 bg-white">
    <div class="mx-auto max-w-7xl px-3 py-10 sm:px-4">
        <div class="flex flex-col">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo-wide.png') }}" alt="{{ $storeSetting->store_name ?? 'Allo! Pizza' }}" class="h-11 w-auto max-w-[160px] shrink-0 object-contain">
                    <div>
                        <p class="text-sm font-bold text-stone-900">{{ $storeSetting->store_name ?? 'Allo! Pizza' }}</p>
                        <p class="mt-0.5 text-sm text-stone-500">Топла пица с бърза доставка в Русе</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-stone-600">
                    <span class="inline-flex items-center gap-1.5 font-medium text-stone-800">
                        <span class="h-2 w-2 rounded-full {{ $storeIsOpen ? 'bg-green-500' : 'bg-stone-400' }}"></span>
                        {{ $storeIsOpen ? 'Отворено' : 'Затворено' }}
                    </span>
                    @if ($workingHoursMessage)
                        <span>{{ $workingHoursMessage }}</span>
                    @endif
                    @if ($storeSetting->average_delivery_time)
                        <span>Доставка до {{ $storeSetting->average_delivery_time }} мин.</span>
                    @endif
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-2 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-4 sm:gap-y-1">
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="text-stone-600 transition hover:text-brand-600">
                    {{ $storeSetting->store_address ?? 'гр. Русе, ул. „Мария Луиза“, 22' }}
                </a>
                <x-store-contact-links link-class="font-bold text-brand-600 hover:text-brand-700" />
                <a href="https://www.facebook.com/share/1BaUfp47Gd/"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Facebook страницата на {{ $storeSetting->store_name ?? 'Allo! Pizza' }}"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-brand-600 text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.414c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.974h-1.513c-1.49 0-1.956.931-1.956 1.887v2.26h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                    </svg>
                    <span class="sr-only">Facebook</span>
                </a>
            </div>

            <nav aria-label="Футър навигация" class="mt-8 flex flex-wrap gap-x-4 gap-y-2 border-t border-stone-100 pt-8 text-sm">
                <a href="{{ route('menu') }}" class="text-stone-600 transition hover:text-brand-600">Меню</a>
                @if ($showLunchMenuNav)
                    <a href="{{ route('lunch.index') }}" class="text-stone-600 transition hover:text-brand-600">Обедно меню</a>
                @endif
                @if ($showNewMenuNav)
                    <a href="{{ route('new-menu.index') }}" class="text-stone-600 transition hover:text-brand-600">Ново в менюто</a>
                @endif
                <a href="{{ route('cart') }}" class="text-stone-600 transition hover:text-brand-600">Количка</a>
                <a href="{{ route('pages.show', 'za-nas') }}" class="text-stone-600 transition hover:text-brand-600">За нас</a>
                <a href="{{ route('pages.show', 'dostavka') }}" class="text-stone-600 transition hover:text-brand-600">Доставка</a>
                <a href="{{ route('pages.show', 'kontakti') }}" class="text-stone-600 transition hover:text-brand-600">Контакти</a>
                @auth
                    <a href="{{ route('account.index') }}" class="text-stone-600 transition hover:text-brand-600">Профил</a>
                @else
                    <a href="{{ route('login') }}" class="text-stone-600 transition hover:text-brand-600">Вход</a>
                @endauth
                <a href="{{ route('pages.show', 'obshti-usloviya') }}" class="text-stone-600 transition hover:text-brand-600">Общи условия</a>
                <a href="{{ route('pages.show', 'politika-za-poveritelnost') }}" class="text-stone-600 transition hover:text-brand-600">Поверителност</a>
                <a href="{{ route('pages.show', 'politika-za-biskvitki') }}" class="text-stone-600 transition hover:text-brand-600">Бисквитки</a>
                <button type="button" data-cookie-settings-open class="text-left text-stone-600 transition hover:text-brand-600">
                    Настройки за бисквитки
                </button>
            </nav>

            <p class="mt-8 border-t border-stone-100 pt-6 text-xs text-stone-400">
                &copy; {{ date('Y') }} {{ $storeSetting->store_name ?? 'Allo! Pizza' }}. Всички права запазени.
            </p>
        </div>
    </div>
</footer>
