@php
    use App\Services\DeliveryService;

    $deliveryService = app(DeliveryService::class);
    $zonePolygon = $deliveryService->zonePolygon();
    $insidePrice = (float) ($storeSetting->delivery_inside_price ?? 2);
    $outsidePrice = (float) ($storeSetting->delivery_outside_price ?? 3);
    $storeLat = (float) ($storeSetting->store_lat ?? 43.8407475);
    $storeLng = (float) ($storeSetting->store_lng ?? 25.9549665);
    $googleMapsKey = config('services.google_maps.key');
    $mapId = 'delivery-zone-map-' . uniqid();
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap gap-3 text-sm">
        <div class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 py-2">
            <span class="h-3 w-3 rounded-sm bg-brand-500/30 ring-2 ring-brand-500"></span>
            <span><strong>{{ money($insidePrice) }}</strong> в района</span>
        </div>
        <div class="inline-flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 py-2">
            <span class="h-3 w-3 rounded-sm bg-stone-300 ring-2 ring-stone-400"></span>
            <span><strong>{{ money($outsidePrice) }}</strong> извън района</span>
        </div>
    </div>

    <div
        id="{{ $mapId }}"
        class="h-72 w-full overflow-hidden rounded-2xl border border-stone-200 bg-stone-100 sm:h-80 lg:h-[360px]"
        data-store-lat="{{ $storeLat }}"
        data-store-lng="{{ $storeLng }}"
        data-store-logo="{{ asset('images/logo-map.png') }}"
        data-inside-price="{{ $insidePrice }}"
        data-outside-price="{{ $outsidePrice }}"
        data-polygon='@json($zonePolygon)'
    >
        <div class="flex h-full items-center justify-center px-4 text-center text-sm font-semibold text-stone-500">
            Зареждане на картата...
        </div>
    </div>

    <p id="{{ $mapId }}-status" class="hidden text-sm"></p>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const maps = [];

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

                function showMapMessage(mapEl, message, isError = true) {
                    mapEl.innerHTML = `
                        <div class="flex h-full items-center justify-center px-5 text-center text-sm font-semibold ${isError ? 'text-brand-600' : 'text-stone-500'}">
                            ${message}
                        </div>
                    `;
                }

                function initDeliveryZoneMap(mapEl) {
                    if (mapEl.dataset.initialized === '1') {
                        return;
                    }

                    mapEl.dataset.initialized = '1';

                    const storeLat = parseFloat(mapEl.dataset.storeLat);
                    const storeLng = parseFloat(mapEl.dataset.storeLng);
                    const storeLogoUrl = mapEl.dataset.storeLogo;
                    const insidePrice = parseFloat(mapEl.dataset.insidePrice);
                    const outsidePrice = parseFloat(mapEl.dataset.outsidePrice);
                    const polygon = JSON.parse(mapEl.dataset.polygon || '[]');
                    const statusEl = document.getElementById(mapEl.id + '-status');

                    if (typeof google === 'undefined' || !google.maps) {
                        showMapMessage(mapEl, 'Google Maps не се зареди. Проверете GOOGLE_MAPS_API_KEY и ограниченията за домейна.');
                        return;
                    }

                    const mapInstance = new google.maps.Map(mapEl, {
                        center: { lat: storeLat, lng: storeLng },
                        zoom: 13,
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

                    let zonePolygon = null;
                    let checkMarker = null;

                    if (polygon.length >= 3) {
                        zonePolygon = new google.maps.Polygon({
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
                        mapInstance.fitBounds(bounds, 40);
                    }

                    function updateStatus(lat, lng) {
                        if (!statusEl) {
                            return;
                        }

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
                            : 'Избраната точка е извън района — ' + formatMoney(outsidePrice);
                    }

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

                    maps.push(mapInstance);
                }

                function initAllDeliveryZoneMaps() {
                    document.querySelectorAll('[data-polygon]').forEach((mapEl) => {
                        if (mapEl.id && mapEl.id.startsWith('delivery-zone-map-')) {
                            initDeliveryZoneMap(mapEl);
                        }
                    });
                }

                window.initDeliveryZonePageMaps = function () {
                    initAllDeliveryZoneMaps();
                };

                window.handleDeliveryZonePageMapsError = function () {
                    document.querySelectorAll('[id^="delivery-zone-map-"]').forEach((mapEl) => {
                        showMapMessage(mapEl, 'Google Maps не се зареди. Проверете API ключа и разрешените домейни в Google Cloud.');
                    });
                };

                window.gm_authFailure = window.gm_authFailure || function () {
                    window.handleDeliveryZonePageMapsError && window.handleDeliveryZonePageMapsError();
                };
            })();
        </script>
        @if ($googleMapsKey)
            <script
                src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&language=bg&region=BG&callback=initDeliveryZonePageMaps&loading=async&auth_referrer_policy=origin"
                async
                defer
                onerror="window.handleDeliveryZonePageMapsError && window.handleDeliveryZonePageMapsError()"></script>
        @else
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    document.querySelectorAll('[id^="delivery-zone-map-"]').forEach((mapEl) => {
                        mapEl.innerHTML = '<div class="flex h-full items-center justify-center px-5 text-center text-sm font-semibold text-brand-600">Липсва GOOGLE_MAPS_API_KEY в конфигурацията.</div>';
                    });
                });
            </script>
        @endif
    @endpush
@endonce
