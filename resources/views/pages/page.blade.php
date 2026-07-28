@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPage($page, route('pages.show', $page->slug));
@endphp

@section('content')
    @push('styles')
        <style>
            .rich-content {
                color: #292524;
                font-size: 1rem;
                line-height: 1.75;
                overflow-wrap: anywhere;
            }

            .rich-content > :first-child {
                margin-top: 0;
            }

            .rich-content > :last-child {
                margin-bottom: 0;
            }

            .rich-content p {
                margin: 0 0 1rem;
            }

            .rich-content h2 {
                margin: 2.25rem 0 1rem;
                color: #1c1917;
                font-size: 1.5rem;
                font-weight: 800;
                line-height: 1.3;
            }

            .rich-content h3 {
                margin: 1.75rem 0 0.75rem;
                color: #1c1917;
                font-size: 1.25rem;
                font-weight: 700;
                line-height: 1.4;
            }

            .rich-content ul,
            .rich-content ol {
                margin: 1rem 0;
                padding-left: 1.5rem;
            }

            .rich-content ul {
                list-style: disc;
            }

            .rich-content ol {
                list-style: decimal;
            }

            .rich-content li {
                margin: 0.4rem 0;
                padding-left: 0.25rem;
            }

            .rich-content li > p {
                margin: 0;
            }

            .rich-content strong {
                color: #1c1917;
                font-weight: 700;
            }

            .rich-content a {
                color: #c5171c;
                font-weight: 600;
                text-decoration: underline;
                text-underline-offset: 2px;
            }

            .rich-content blockquote {
                margin: 1.5rem 0;
                border-left: 4px solid #eb1c22;
                border-radius: 0 0.75rem 0.75rem 0;
                background: #feeced;
                padding: 0.75rem 1rem;
                color: #57534e;
                font-style: italic;
            }

            .rich-content pre {
                margin: 1.5rem 0;
                overflow-x: auto;
                border-radius: 0.75rem;
                background: #1c1917;
                padding: 1rem;
                color: #fafaf9;
            }

            .rich-content img {
                margin: 1.5rem auto;
                height: auto;
                max-width: 100%;
                border-radius: 0.75rem;
            }

            @media (max-width: 640px) {
                .rich-content {
                    font-size: 0.95rem;
                    line-height: 1.7;
                }

                .rich-content h2 {
                    font-size: 1.3rem;
                }

                .rich-content h3 {
                    font-size: 1.15rem;
                }
            }
        </style>
    @endpush
    @php
        use App\Services\DeliveryService;
        use Illuminate\Support\Facades\Storage;

        $featuredImage = ($page->slug !== 'dostavka' && $page->featured_image)
            ? Storage::url($page->featured_image)
            : null;

        $zonePolygon = [];
        $insidePrice = (float) ($storeSetting->delivery_inside_price ?? 2);
        $outsidePrice = (float) ($storeSetting->delivery_outside_price ?? 3);
        $storeLat = (float) ($storeSetting->store_lat ?? 43.8407475);
        $storeLng = (float) ($storeSetting->store_lng ?? 25.9549665);
        $googleMapsKey = config('services.google_maps.key');

        if ($page->slug === 'dostavka') {
            $zonePolygon = app(DeliveryService::class)->zonePolygon();
        }
    @endphp
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => $page->title],
    ]" />

    @if ($page->slug === 'dostavka')
        <div class="mb-6 space-y-4 overflow-hidden rounded-[1.5rem] border border-stone-200 bg-white p-4 shadow-soft sm:rounded-[2rem] sm:p-5">
            <div
                id="delivery-zone-page-map"
                class="h-[280px] w-full overflow-hidden rounded-2xl border border-stone-200 bg-stone-100 sm:h-[360px] lg:h-[420px]"
                data-store-lat="{{ $storeLat }}"
                data-store-lng="{{ $storeLng }}"
                data-store-logo="{{ asset('images/logo-map.png') }}"
                data-inside-price="{{ $insidePrice }}"
                data-outside-price="{{ $outsidePrice }}"
                data-polygon='@json($zonePolygon)'
            ></div>

            <div class="flex flex-wrap gap-3 text-sm">
                <div class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 py-2 shadow-soft">
                    <span class="h-3 w-3 rounded-sm bg-brand-500/30 ring-2 ring-brand-500"></span>
                    <span><strong>{{ money($insidePrice) }}</strong> в района</span>
                </div>
                <div class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 py-2 shadow-soft">
                    <span class="h-3 w-3 rounded-sm bg-stone-300 ring-2 ring-stone-400"></span>
                    <span>Извън района — уточнява се допълнително</span>
                </div>
            </div>

            <p id="delivery-zone-page-status" class="hidden text-sm text-stone-600"></p>
            <p class="text-sm text-stone-500">Кликнете на картата, за да проверите дали адресът ви попада в района.</p>
        </div>
    @elseif ($featuredImage)
        <div class="mb-6 overflow-hidden rounded-[1.5rem] shadow-soft sm:rounded-[2rem]">
            <img src="{{ $featuredImage }}" alt="{{ $page->title }}" class="aspect-[21/9] w-full object-cover">
        </div>
    @endif

    <h1 class="mb-6 text-3xl font-bold">{{ $page->title }}</h1>

    @if ($page->slug === 'kontakti')
        <div class="grid gap-6 lg:grid-cols-2 lg:items-stretch lg:gap-8">
            <div class="rich-content prose min-h-[280px] max-w-none rounded-2xl border border-stone-200 bg-white p-6 lg:min-h-[360px]">
                {!! $page->content !!}
            </div>
            <div class="min-h-[280px] overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-soft lg:min-h-[360px]">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d719.4319000327891!2d25.955713585583943!3d43.8407457680951!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40ae60bf8b946307%3A0xcfe9be56ae94a509!2z0LYu0LouINCl0YrRiNC-0LLQtSwg0YPQuy4g4oCe0JzQsNGA0LjRjyDQm9GD0LjQt9Cw4oCcIDIyLCA3MDEyINCg0YPRgdC1!5e0!3m2!1sbg!2sbg!4v1782300298988!5m2!1sbg!2sbg"
                    class="h-full min-h-[280px] w-full border-0 lg:min-h-[360px]"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    title="Allo! Pizza на карта"></iframe>
            </div>
        </div>
    @elseif ($page->slug === 'dostavka')
        <div class="rich-content prose max-w-none rounded-2xl border border-stone-200 bg-white p-6">
            {!! $page->content !!}

            <ul class="not-prose mt-6 space-y-2 text-sm text-stone-700">
                <li class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                    <span><strong>{{ money((float) ($storeSetting->delivery_inside_price ?? 2)) }}</strong> — доставка в района (очертан на картата)</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-stone-400"></span>
                    <span>Уточнява се допълнително — доставка извън района</span>
                </li>
                @if ($storeSetting->free_delivery_over)
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-500"></span>
                        <span>Безплатна доставка при поръчка над {{ money((float) $storeSetting->free_delivery_over) }}</span>
                    </li>
                @endif
                <li class="flex items-start gap-2">
                    <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-gold-500"></span>
                    <span>Средно време за доставка: до {{ $storeSetting->average_delivery_time ?? 30 }} мин.</span>
                </li>
            </ul>

            <p class="not-prose mt-4 text-sm text-stone-500">
                {{ \App\Support\DeliveryZone::boundaryDescription() }}
            </p>
        </div>
    @else
        <div class="rich-content prose max-w-none rounded-2xl border border-stone-200 bg-white p-6">
            {!! $page->content !!}
        </div>
    @endif

    @if (! empty($galleryImages))
        <section class="mt-8">
            <h2 class="mb-4 text-xl font-extrabold tracking-tight text-stone-900">Заведението</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($galleryImages as $image)
                    <div class="aspect-square overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-soft">
                        <img src="{{ $image }}" alt="Allo! Pizza" loading="lazy"
                             class="h-full w-full object-cover transition duration-300 hover:scale-105">
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($page->slug === 'dostavka')
        @push('styles')
            <style>
                .gm-style img,
                .gm-style svg,
                .gm-style-cc img,
                #delivery-zone-page-map img {
                    max-width: none !important;
                }
            </style>
        @endpush

        @push('scripts')
            <script>
                (function () {
                    const hasGoogleMapsKey = @json(filled($googleMapsKey));
                    let mapInitialized = false;

                    function pointInPolygon(lat, lng, points) {
                        let inside = false;
                        for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
                            const yi = parseFloat(points[i].lat);
                            const xi = parseFloat(points[i].lng);
                            const yj = parseFloat(points[j].lat);
                            const xj = parseFloat(points[j].lng);
                            const intersects = ((yi > lat) !== (yj > lat))
                                && (lng < (xj - xi) * (lat - yi) / ((yj - yi) || 1e-12) + xi);
                            if (intersects) inside = !inside;
                        }
                        return inside;
                    }

                    function formatMoney(amount) {
                        return new Intl.NumberFormat('bg-BG', {
                            style: 'currency',
                            currency: 'EUR',
                            minimumFractionDigits: 2,
                        }).format(amount);
                    }

                    function showMapMessage(message) {
                        const mapEl = document.getElementById('delivery-zone-page-map');
                        if (!mapEl) return;

                        mapEl.innerHTML = '<div class="flex h-full items-center justify-center px-5 text-center text-sm font-semibold text-brand-600">' + message + '</div>';
                    }

                    function initDeliveryZonePageMap() {
                        if (mapInitialized) return;

                        const mapEl = document.getElementById('delivery-zone-page-map');
                        if (!mapEl) return;

                        if (!hasGoogleMapsKey) {
                            showMapMessage('Липсва GOOGLE_MAPS_API_KEY в конфигурацията.');
                            return;
                        }

                        if (typeof google === 'undefined' || !google.maps) {
                            showMapMessage('Google Maps не се зареди. Проверете GOOGLE_MAPS_API_KEY и ограниченията за домейна.');
                            return;
                        }

                        mapInitialized = true;

                        const storeLat = parseFloat(mapEl.dataset.storeLat);
                        const storeLng = parseFloat(mapEl.dataset.storeLng);
                        const storeLogoUrl = mapEl.dataset.storeLogo;
                        const insidePrice = parseFloat(mapEl.dataset.insidePrice);
                        const outsidePrice = parseFloat(mapEl.dataset.outsidePrice);
                        const polygon = JSON.parse(mapEl.dataset.polygon || '[]');
                        const statusEl = document.getElementById('delivery-zone-page-status');

                        const mapInstance = new google.maps.Map(mapEl, {
                            center: { lat: storeLat, lng: storeLng },
                            zoom: 14,
                            mapTypeControl: false,
                            streetViewControl: false,
                            fullscreenControl: true,
                        });

                        new google.maps.Marker({
                            map: mapInstance,
                            position: { lat: storeLat, lng: storeLng },
                            title: 'Allo! Pizza',
                            icon: {
                                url: storeLogoUrl,
                                scaledSize: new google.maps.Size(58, 58),
                                anchor: new google.maps.Point(11, 56),
                            },
                            zIndex: 1000,
                        });

                        if (polygon.length >= 3) {
                            new google.maps.Polygon({
                                paths: polygon.map((point) => ({
                                    lat: parseFloat(point.lat),
                                    lng: parseFloat(point.lng),
                                })),
                                strokeColor: '#EB1C22',
                                strokeOpacity: 1,
                                strokeWeight: 3,
                                fillColor: '#EB1C22',
                                fillOpacity: 0.22,
                                clickable: false,
                                map: mapInstance,
                            });

                            const bounds = new google.maps.LatLngBounds();
                            polygon.forEach((point) => bounds.extend({
                                lat: parseFloat(point.lat),
                                lng: parseFloat(point.lng),
                            }));
                            bounds.extend({ lat: storeLat, lng: storeLng });
                            mapInstance.fitBounds(bounds, 48);
                        }

                        function refreshMapLayout() {
                            google.maps.event.trigger(mapInstance, 'resize');

                            if (polygon.length >= 3) {
                                const bounds = new google.maps.LatLngBounds();
                                polygon.forEach((point) => bounds.extend({
                                    lat: parseFloat(point.lat),
                                    lng: parseFloat(point.lng),
                                }));
                                bounds.extend({ lat: storeLat, lng: storeLng });
                                mapInstance.fitBounds(bounds, 48);
                            }
                        }

                        google.maps.event.addListenerOnce(mapInstance, 'idle', refreshMapLayout);
                        window.addEventListener('resize', refreshMapLayout);

                        let checkMarker = null;

                        mapInstance.addListener('click', (event) => {
                            const lat = event.latLng.lat();
                            const lng = event.latLng.lng();

                            if (!checkMarker) {
                                checkMarker = new google.maps.Marker({
                                    map: mapInstance,
                                    draggable: true,
                                });

                                checkMarker.addListener('dragend', () => {
                                    const pos = checkMarker.getPosition();
                                    updateStatus(pos.lat(), pos.lng());
                                });
                            }

                            checkMarker.setPosition({ lat, lng });
                            updateStatus(lat, lng);
                        });

                        function updateStatus(lat, lng) {
                            if (!statusEl) return;

                            statusEl.classList.remove('hidden');

                            if (polygon.length < 3) {
                                statusEl.className = 'text-sm text-green-700';
                                statusEl.textContent = 'Доставка — ' + formatMoney(insidePrice);
                                return;
                            }

                            const inside = pointInPolygon(lat, lng, polygon);
                            statusEl.className = 'text-sm ' + (inside ? 'text-green-700' : 'text-brand-600');
                            statusEl.textContent = inside
                                ? 'Избраната точка е в района — ' + formatMoney(insidePrice)
                                : 'Избраната точка е извън района — уточнява се допълнително';
                        }
                    }

                    window.initDeliveryZonePageMap = initDeliveryZonePageMap;

                    window.handleDeliveryZonePageMapError = function () {
                        showMapMessage('Google Maps не се зареди. Проверете API ключа и разрешените домейни в Google Cloud.');
                    };

                    window.gm_authFailure = function () {
                        showMapMessage('Google Maps API ключът не е разрешен за този домейн.');
                    };

                    if (!hasGoogleMapsKey) {
                        showMapMessage('Липсва GOOGLE_MAPS_API_KEY в конфигурацията.');
                    }
                })();
            </script>
            @if ($googleMapsKey)
                <script
                    src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=bg&region=BG&callback=initDeliveryZonePageMap&loading=async"
                    async
                    defer
                    onerror="window.handleDeliveryZonePageMapError && window.handleDeliveryZonePageMapError()"></script>
            @endif
        @endpush
    @endif
@endsection
