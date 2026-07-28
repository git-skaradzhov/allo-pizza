@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Поръчка');
    $savedAddresses = auth()->user()?->customer?->addresses ?? collect();
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Количка', 'url' => route('cart')],
        ['label' => 'Поръчка'],
    ]" />

    <h1 class="mb-6 text-3xl font-extrabold tracking-tight">Поръчка</h1>

    @unless ($isOpen)
        <div class="mb-6 rounded-2xl bg-gold-500/10 px-4 py-3 text-brand-700">
            {{ app(\App\Services\StoreService::class)->ordersClosedMessage() }}
        </div>
    @endunless

    @if (session('error'))
        <div class="mb-6 rounded-2xl bg-brand-50 px-4 py-3 text-brand-700">{{ session('error') }}</div>
    @endif

    <div class="grid gap-8 lg:grid-cols-2">
        <form method="POST" action="{{ route('checkout.store') }}" class="space-y-5 rounded-3xl border border-stone-200 bg-white p-6" id="checkout-form">
            @csrf

            <div>
                <label for="customer_name" class="block text-sm font-semibold">Име</label>
                <input id="customer_name" name="customer_name" type="text" value="{{ old('customer_name', auth()->user()?->name) }}" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                @error('customer_name') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="customer_phone" class="block text-sm font-semibold">Телефон</label>
                    <input id="customer_phone" name="customer_phone" type="text" value="{{ old('customer_phone', auth()->user()?->customer?->phone) }}" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                    @error('customer_phone') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="customer_email" class="block text-sm font-semibold">Имейл</label>
                    <input id="customer_email" name="customer_email" type="text" inputmode="email" autocomplete="email" autocapitalize="none" spellcheck="false" value="{{ old('customer_email', auth()->user()?->email) }}" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                    @error('customer_email') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <span class="block text-sm font-semibold">Начин на получаване</span>
                <div class="mt-2 grid grid-cols-2 gap-2 rounded-2xl bg-stone-100 p-1.5">
                    <label class="cursor-pointer">
                        <input type="radio" name="delivery_type" value="delivery" class="peer sr-only delivery-type" {{ old('delivery_type', 'delivery') === 'delivery' ? 'checked' : '' }}>
                        <span class="block rounded-xl px-3 py-2.5 text-center text-sm font-semibold text-stone-600 transition peer-checked:bg-white peer-checked:text-brand-600 peer-checked:shadow-soft">Доставка</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="delivery_type" value="pickup" class="peer sr-only delivery-type" {{ old('delivery_type') === 'pickup' ? 'checked' : '' }}>
                        <span class="block rounded-xl px-3 py-2.5 text-center text-sm font-semibold text-stone-600 transition peer-checked:bg-white peer-checked:text-brand-600 peer-checked:shadow-soft">Вземане от място</span>
                    </label>
                </div>
            </div>

            @php
                $defaultDeliveryType = old('delivery_type', 'delivery');
                $defaultPaymentMethod = old('payment_method', $defaultDeliveryType === 'pickup' ? 'pay_at_store' : 'cash_on_delivery');
            @endphp

            <div id="location-block" class="space-y-3">
                <div id="delivery-fields" class="space-y-3 {{ $defaultDeliveryType === 'pickup' ? 'hidden' : '' }}">
                    @if ($savedAddresses->isNotEmpty())
                        <div>
                            <label class="block text-sm font-semibold">Запазен адрес</label>
                            <select id="saved-address" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                                <option value="">— Нов адрес —</option>
                                @foreach ($savedAddresses as $address)
                                    <option value="{{ $address->address_line }}, {{ $address->city }}"
                                            data-lat="{{ $address->lat }}" data-lng="{{ $address->lng }}">{{ $address->label ?? 'Адрес' }}: {{ $address->address_line }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="address-search" class="block text-sm font-semibold">Намери адрес на картата</label>
                        <div class="mt-1 flex gap-2">
                            <input type="text" id="address-search" placeholder="Започнете да пишете ул. и номер…" autocomplete="off" class="w-full rounded-xl border-stone-300 text-sm focus:border-brand-400 focus:ring-brand-400">
                            <button type="button" id="address-search-btn" class="shrink-0 rounded-xl bg-stone-800 px-4 text-sm font-semibold text-white hover:bg-stone-900">Търси</button>
                        </div>
                        <p class="mt-1 text-xs text-stone-500">Русе и населени места в област Русе — улица или село/квартал.</p>
                    </div>

                    <p id="zone-status" class="hidden text-sm"></p>

                    <input type="hidden" id="delivery_lat" name="delivery_lat" value="{{ old('delivery_lat') }}">
                    <input type="hidden" id="delivery_lng" name="delivery_lng" value="{{ old('delivery_lng') }}">

                    <div>
                        <label for="delivery_address" class="block text-sm font-semibold">Адрес за доставка</label>
                        <textarea id="delivery_address" name="delivery_address" rows="2" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">{{ old('delivery_address') }}</textarea>
                        @error('delivery_address') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div id="pickup-info" class="{{ $defaultDeliveryType === 'pickup' ? '' : 'hidden' }} rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                    <p class="text-sm font-semibold text-stone-900">Адрес на пицарията</p>
                    <p class="mt-1 text-sm text-stone-600">{{ $settings->store_address }}</p>
                </div>

                <div id="map"
                     class="h-56 w-full overflow-hidden rounded-2xl border border-stone-200"
                     data-store-lat="{{ (float) ($settings->store_lat ?? 43.8356) }}"
                     data-store-lng="{{ (float) ($settings->store_lng ?? 25.9657) }}"
                     data-store-logo="{{ asset('images/logo-map.png') }}"
                     data-store-address="{{ $settings->store_address }}"
                     data-inside-price="{{ (float) $deliveryInsidePrice }}"
                     data-outside-price="{{ (float) $deliveryOutsidePrice }}"
                     data-free-over="{{ $freeDeliveryOver ?? '' }}"
                     data-delivery-radius="{{ (float) ($settings->delivery_radius_km ?? 15) }}"
                     data-polygon='@json($zonePolygon)'
                     data-old-lat="{{ old('delivery_lat') }}"
                     data-old-lng="{{ old('delivery_lng') }}">
                    <div class="flex h-full items-center justify-center bg-stone-100 px-4 text-center text-sm font-semibold text-stone-500">
                        Зареждане на Google Maps...
                    </div>
                </div>
            </div>

            <div>
                <span class="block text-sm font-semibold">Начин на плащане</span>
                <div class="mt-2">
                    <div id="payment-delivery" class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium text-stone-700 {{ $defaultDeliveryType === 'pickup' ? 'hidden' : '' }}">
                        Плащане при доставка
                    </div>
                    <div id="payment-pickup" class="rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium text-stone-700 {{ $defaultDeliveryType === 'pickup' ? '' : 'hidden' }}">
                        Плащане на място
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="payment_method" value="{{ $defaultPaymentMethod }}">
            </div>

            <div>
                <label for="customer_note" class="block text-sm font-semibold">Бележка към поръчката</label>
                <textarea id="customer_note" name="customer_note" rows="3" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">{{ old('customer_note') }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-2xl bg-brand-500 px-4 py-4 text-lg font-bold text-white transition hover:bg-brand-600 disabled:bg-stone-300" @disabled(! $isOpen)>
                Потвърди поръчката
            </button>
        </form>

        <div class="h-fit rounded-3xl border border-stone-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-bold">Обобщение</h2>

            @if ($settings->isBundlePromotionEnabled() && ($bundle->eligibleQuantity ?? 0) > 0)
                <div class="mb-4 rounded-xl border border-brand-200 bg-brand-50 px-3 py-2 text-sm text-brand-800">
                    @if ($bundle->isActive())
                        🎁 {{ $settings->bundlePromotionLabel() }}:
                        {{ $bundle->freeCount }} {{ $bundle->freeCount === 1 ? 'безплатен' : 'безплатни' }}
                        {{ $bundle->freeCount === 1 ? 'продукт' : 'продукти' }}
                    @else
                        🎁 Добавете още {{ $bundle->remainingUntilFree }} {{ $bundle->remainingUntilFree === 1 ? 'продукт' : 'продукти' }} за 1 безплатен
                    @endif
                </div>
            @endif

            @if ($promoIgnored ?? false)
                <p class="mb-4 text-sm text-brand-700">Промо кодът не се комбинира с 4+1 промоцията.</p>
            @endif

            <div class="space-y-3 text-sm">
                @foreach ($cart->items as $item)
                    <div class="flex justify-between gap-4 border-b border-stone-100 pb-3 last:border-0 last:pb-0">
                        <div class="min-w-0">
                            <p class="font-medium text-stone-900">{{ $item->displayName() }} × {{ $item->quantity }}</p>
                            @if (! $item->isLunchItem() && $item->variant)
                                <p class="text-xs text-stone-500">{{ $item->variant->name }} ({{ $item->variant->size_label }})</p>
                            @endif
                            <x-order-item-options :options="$item->options ?? []" />
                            @if ($item->note)
                                <p class="mt-0.5 text-xs italic text-stone-400">„{{ $item->note }}"</p>
                            @endif
                        </div>
                        <span class="shrink-0 font-semibold">{{ money($item->total_price) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 space-y-2 border-t border-stone-200 pt-4 text-sm">
                <div class="flex justify-between">
                    <span class="text-stone-500">Междинна сума</span>
                    <span class="font-semibold">{{ money($subtotal) }}</span>
                </div>
                @if ($settings->isBundlePromotionEnabled() && $bundleDiscount > 0)
                    <div class="flex justify-between text-green-700">
                        <span>{{ $settings->bundlePromotionLabel() }}</span>
                        <span class="font-semibold">−{{ money($bundleDiscount) }}</span>
                    </div>
                @endif
                @if ($promoDiscount > 0)
                    <div class="flex justify-between text-green-700">
                        <span>Промо код{{ $appliedPromo ? ' ('.$appliedPromo->code.')' : '' }}</span>
                        <span class="font-semibold">−{{ money($promoDiscount) }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-stone-500">Доставка</span>
                    <span class="font-semibold" id="summary-delivery" data-delivery="{{ (float) $deliveryPrice }}">{{ $deliveryPrice > 0 ? money($deliveryPrice) : 'Безплатна' }}</span>
                </div>
                <p class="text-xs text-stone-400">
                    {{ money($deliveryInsidePrice) }} в района · {{ money($deliveryOutsidePrice) }} извън района
                </p>
                @if ($settings->free_delivery_over)
                    <p class="text-xs text-stone-400">Безплатна доставка над {{ money($settings->free_delivery_over) }}</p>
                @endif
            </div>
            <div class="mt-3 flex justify-between border-t border-stone-200 pt-3 text-lg font-extrabold">
                <span>Общо</span>
                <span class="text-brand-600" id="summary-total">{{ money(max(0, $subtotal - $discount) + $deliveryPrice) }}</span>
            </div>
            <p class="mt-3 text-xs text-stone-400">Минимална поръчка: {{ money($settings->minimum_order_amount) }}</p>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const hasGoogleMapsKey = @json(filled($googleMapsKey));
                const subtotal = {{ (float) $subtotal }};
                const discount = {{ (float) $discount }};
                const deliveryEl = document.getElementById('summary-delivery');
                const totalEl = document.getElementById('summary-total');
                const deliveryFields = document.getElementById('delivery-fields');
                const pickupInfo = document.getElementById('pickup-info');
                const savedSelect = document.getElementById('saved-address');
                const addressField = document.getElementById('delivery_address');
                const latField = document.getElementById('delivery_lat');
                const lngField = document.getElementById('delivery_lng');
                const zoneStatus = document.getElementById('zone-status');
                const paymentDelivery = document.getElementById('payment-delivery');
                const paymentPickup = document.getElementById('payment-pickup');
                const paymentMethodField = document.getElementById('payment_method');
                const checkoutForm = document.getElementById('checkout-form');
                const customerEmailField = document.getElementById('customer_email');
                const mapEl = document.getElementById('map');
                const storeLat = parseFloat(mapEl.dataset.storeLat);
                const storeLng = parseFloat(mapEl.dataset.storeLng);
                const storeLogoUrl = mapEl.dataset.storeLogo;
                const insidePrice = parseFloat(mapEl.dataset.insidePrice || 0);
                const outsidePrice = parseFloat(mapEl.dataset.outsidePrice || 0);
                const freeOver = mapEl.dataset.freeOver ? parseFloat(mapEl.dataset.freeOver) : null;
                const polygon = JSON.parse(mapEl.dataset.polygon || '[]');
                const deliveryRadiusKm = parseFloat(mapEl.dataset.deliveryRadius || '0');
                let currentDeliveryFee = insidePrice;
                let mapInstance = null;
                let deliveryMarker = null;
                let zonePolygon = null;
                let deliveryMode = true;
                let mapInitialized = false;

                function formatMoney(value) {
                    return value.toFixed(2) + ' €';
                }

                function normalizeEmailField() {
                    if (!customerEmailField) {
                        return;
                    }

                    customerEmailField.value = customerEmailField.value.replace(/\s+/g, '').trim();
                }

                customerEmailField?.addEventListener('input', normalizeEmailField);
                checkoutForm?.addEventListener('submit', normalizeEmailField);

                function pointInPolygon(lat, lng, points) {
                    if (!points || points.length < 3) return false;
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

                function deliveryFeeForPoint(lat, lng) {
                    if (freeOver !== null && subtotal >= freeOver) return 0;
                    if (!lat || !lng) return insidePrice;
                    if (polygon.length >= 3) {
                        return pointInPolygon(lat, lng, polygon) ? insidePrice : outsidePrice;
                    }
                    return insidePrice;
                }

                function updateZoneStatus(lat, lng) {
                    if (!lat || !lng) {
                        zoneStatus.classList.add('hidden');
                        return;
                    }

                    zoneStatus.classList.remove('hidden');
                    currentDeliveryFee = deliveryFeeForPoint(lat, lng);

                    if (freeOver !== null && subtotal >= freeOver) {
                        zoneStatus.className = 'mt-2 text-sm text-green-700';
                        zoneStatus.textContent = 'Безплатна доставка за тази поръчка.';
                        return;
                    }

                    if (polygon.length >= 3) {
                        const inside = pointInPolygon(lat, lng, polygon);
                        zoneStatus.className = 'mt-2 text-sm ' + (inside ? 'text-green-700' : 'text-brand-600');
                        zoneStatus.textContent = inside
                            ? 'В района за доставка — ' + formatMoney(insidePrice)
                            : 'Извън района — ' + formatMoney(outsidePrice);
                        return;
                    }

                    zoneStatus.className = 'mt-2 text-sm text-green-700';
                    zoneStatus.textContent = 'Доставка — ' + formatMoney(insidePrice);
                }

                function showMapMessage(message, isError = true) {
                    mapEl.innerHTML = `
                        <div class="flex h-full items-center justify-center bg-stone-100 px-5 text-center text-sm font-semibold ${isError ? 'text-brand-600' : 'text-stone-500'}">
                            ${message}
                        </div>
                    `;

                    zoneStatus.classList.remove('hidden');
                    zoneStatus.className = 'mt-2 text-sm ' + (isError ? 'text-brand-600' : 'text-stone-500');
                    zoneStatus.textContent = message;
                }

                function showStatusMessage(message, isError = true) {
                    zoneStatus.classList.remove('hidden');
                    zoneStatus.className = 'mt-2 text-sm ' + (isError ? 'text-brand-600' : 'text-stone-500');
                    zoneStatus.textContent = message;
                }

                function updateTotals() {
                    const type = document.querySelector('.delivery-type:checked')?.value || 'delivery';
                    const isDelivery = type === 'delivery';
                    deliveryMode = isDelivery;

                    deliveryFields.classList.toggle('hidden', !isDelivery);
                    pickupInfo.classList.toggle('hidden', isDelivery);
                    paymentDelivery.classList.toggle('hidden', !isDelivery);
                    paymentPickup.classList.toggle('hidden', isDelivery);
                    paymentMethodField.value = isDelivery ? 'cash_on_delivery' : 'pay_at_store';

                    if (!isDelivery) {
                        latField.value = '';
                        lngField.value = '';
                        zoneStatus.classList.add('hidden');
                    }

                    const lat = parseFloat(latField.value);
                    const lng = parseFloat(lngField.value);
                    const fee = isDelivery ? deliveryFeeForPoint(lat, lng) : 0;
                    currentDeliveryFee = fee;

                    deliveryEl.textContent = fee > 0 ? formatMoney(fee) : (isDelivery ? 'Безплатна' : '—');
                    totalEl.textContent = formatMoney(Math.max(0, subtotal - discount) + fee);

                    if (isDelivery) {
                        updateZoneStatus(lat, lng);
                    }

                    setMapMode(isDelivery);
                }

                function setMapMode(isDelivery) {
                    if (!mapInstance) {
                        return;
                    }

                    if (isDelivery) {
                        if (zonePolygon) {
                            zonePolygon.setMap(mapInstance);
                        }

                        const lat = parseFloat(latField.value);
                        const lng = parseFloat(lngField.value);
                        const hasPoint = !isNaN(lat) && !isNaN(lng);

                        if (deliveryMarker) {
                            deliveryMarker.setDraggable(true);
                            deliveryMarker.setVisible(hasPoint);
                        }

                        // Не връщай картата към зоната, ако вече има избран адрес.
                        if (!hasPoint && polygon.length >= 3) {
                            const bounds = new google.maps.LatLngBounds();
                            polygon.forEach((point) => bounds.extend({ lat: parseFloat(point.lat), lng: parseFloat(point.lng) }));
                            bounds.extend({ lat: storeLat, lng: storeLng });
                            mapInstance.fitBounds(bounds, 40);
                        }
                    } else {
                        if (zonePolygon) {
                            zonePolygon.setMap(null);
                        }

                        if (deliveryMarker) {
                            deliveryMarker.setVisible(false);
                            deliveryMarker.setDraggable(false);
                        }

                        mapInstance.setCenter({ lat: storeLat, lng: storeLng });
                        mapInstance.setZoom(16);
                    }

                    google.maps.event.trigger(mapInstance, 'resize');
                }

                document.querySelectorAll('.delivery-type').forEach((el) => el.addEventListener('change', updateTotals));

                function setPoint(lat, lng, fly) {
                    if (deliveryMode && deliveryRadiusKm > 0) {
                        const toRad = (deg) => deg * Math.PI / 180;
                        const dLat = toRad(lat - storeLat);
                        const dLng = toRad(lng - storeLng);
                        const a = Math.sin(dLat / 2) ** 2
                            + Math.cos(toRad(storeLat)) * Math.cos(toRad(lat)) * Math.sin(dLng / 2) ** 2;
                        const km = 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

                        if (km > deliveryRadiusKm) {
                            if (deliveryMarker) {
                                deliveryMarker.setVisible(true);
                                deliveryMarker.setPosition({ lat, lng });
                            }
                            if (fly && mapInstance) {
                                mapInstance.panTo({ lat, lng });
                                mapInstance.setZoom(13);
                            }
                            latField.value = '';
                            lngField.value = '';
                            addressField.value = '';
                            showStatusMessage('Адресът е извън зоната за доставка.', true);
                            updateTotals();
                            return;
                        }
                    }

                    latField.value = lat.toFixed(7);
                    lngField.value = lng.toFixed(7);

                    if (deliveryMarker) {
                        deliveryMarker.setPosition({ lat, lng });
                    }

                    if (fly && mapInstance) {
                        mapInstance.panTo({ lat, lng });
                        mapInstance.setZoom(15);
                    }

                    updateTotals();
                }

                function storeLogoIcon() {
                    return {
                        url: storeLogoUrl,
                        scaledSize: new google.maps.Size(58, 58),
                        anchor: new google.maps.Point(11, 56),
                    };
                }

                function initGoogleMap() {
                    if (mapInitialized) {
                        return;
                    }

                    mapInitialized = true;

                    if (typeof google === 'undefined' || !google.maps) {
                        updateTotals();
                        showMapMessage('Google Maps не се зареди. Проверете GOOGLE_MAPS_API_KEY и ограниченията за домейна.');
                        return;
                    }

                    mapInstance = new google.maps.Map(mapEl, {
                        center: { lat: storeLat, lng: storeLng },
                        zoom: 13,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false,
                    });

                    new google.maps.Marker({
                        map: mapInstance,
                        position: { lat: storeLat, lng: storeLng },
                        title: 'Allo! Pizza',
                        icon: storeLogoIcon(),
                        zIndex: 1000,
                    });

                    if (polygon.length >= 3) {
                        zonePolygon = new google.maps.Polygon({
                            paths: polygon.map((point) => ({ lat: parseFloat(point.lat), lng: parseFloat(point.lng) })),
                            strokeColor: '#EB1C22',
                            strokeOpacity: 1,
                            strokeWeight: 3,
                            fillColor: '#EB1C22',
                            fillOpacity: 0.22,
                            clickable: false,
                            map: mapInstance,
                        });

                        const bounds = new google.maps.LatLngBounds();
                        polygon.forEach((point) => bounds.extend({ lat: parseFloat(point.lat), lng: parseFloat(point.lng) }));
                        bounds.extend({ lat: storeLat, lng: storeLng });
                        mapInstance.fitBounds(bounds, 40);
                    } else {
                        mapInstance.setCenter({ lat: storeLat, lng: storeLng });
                        mapInstance.setZoom(13);
                    }

                    deliveryMarker = new google.maps.Marker({
                        map: mapInstance,
                        position: { lat: storeLat, lng: storeLng },
                        draggable: true,
                        visible: false,
                    });

                    // Приблизителни граници на област Русе — bias + клиентски филтър.
                    const ruseBounds = new google.maps.LatLngBounds(
                        { lat: 43.40, lng: 25.50 },
                        { lat: 44.15, lng: 26.65 },
                    );

                    deliveryMarker.addListener('dragend', () => {
                        if (!deliveryMode) {
                            return;
                        }

                        const pos = deliveryMarker.getPosition();
                        if (!ruseBounds.contains(pos)) {
                            showStatusMessage('Адресът е извън област Русе. Моля, изберете адрес в Русе.', true);
                            const prevLat = parseFloat(latField.value);
                            const prevLng = parseFloat(lngField.value);
                            if (!isNaN(prevLat) && !isNaN(prevLng)) {
                                deliveryMarker.setPosition({ lat: prevLat, lng: prevLng });
                            }
                            return;
                        }

                        setPoint(pos.lat(), pos.lng(), false);
                        reverseGeocode(pos.lat(), pos.lng());
                    });

                    mapInstance.addListener('click', (event) => {
                        if (!deliveryMode) {
                            return;
                        }

                        if (!ruseBounds.contains(event.latLng)) {
                            showStatusMessage('Адресът е извън област Русе. Моля, изберете адрес в Русе.', true);
                            return;
                        }

                        deliveryMarker.setVisible(true);
                        setPoint(event.latLng.lat(), event.latLng.lng(), false);
                        reverseGeocode(event.latLng.lat(), event.latLng.lng());
                    });

                    const searchInput = document.getElementById('address-search');
                    const geocoder = new google.maps.Geocoder();
                    const placesService = (google.maps.places && mapInstance)
                        ? new google.maps.places.PlacesService(mapInstance)
                        : null;

                    function distanceKm(lat1, lng1, lat2, lng2) {
                        const toRad = (deg) => deg * Math.PI / 180;
                        const dLat = toRad(lat2 - lat1);
                        const dLng = toRad(lng2 - lng1);
                        const a = Math.sin(dLat / 2) ** 2
                            + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
                        return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                    }

                    function componentText(components, type) {
                        const match = (components || []).find((c) => c.types.includes(type));
                        return match ? `${match.long_name} ${match.short_name}`.toLowerCase() : '';
                    }

                    function latLngOf(location) {
                        return {
                            lat: typeof location.lat === 'function' ? location.lat() : location.lat,
                            lng: typeof location.lng === 'function' ? location.lng() : location.lng,
                        };
                    }

                    function isInRuseRegion(result) {
                        const location = result.geometry?.location;
                        if (!location) {
                            return false;
                        }

                        const { lat, lng } = latLngOf(location);

                        if (!ruseBounds.contains({ lat, lng })) {
                            return false;
                        }

                        // Геометрия в bbox на областта е достатъчна (села като Мартен).
                        return distanceKm(storeLat, storeLng, lat, lng) <= 60;
                    }

                    function resultRank(result) {
                        const types = result.types || [];
                        if (types.includes('street_address') || types.includes('premise') || types.includes('subpremise')) {
                            return 0;
                        }
                        if (types.includes('route') || types.includes('intersection')) {
                            return 1;
                        }
                        if (types.includes('neighborhood') || types.includes('sublocality') || types.includes('sublocality_level_1')) {
                            return 2;
                        }
                        if (types.includes('locality') || types.includes('postal_town')) {
                            return 4;
                        }
                        // administrative / political (цяла област и т.н.)
                        return 5;
                    }

                    function queryWantsStreet(query) {
                        return /\d/.test(query) || /ул\.?|улица|бул\.?|булевард|площад|пл\./i.test(query);
                    }

                    function isBareRuseCity(result) {
                        const components = result.address_components || [];
                        const hasRoute = components.some((c) => c.types.includes('route'));
                        const hasStreetNumber = components.some((c) => c.types.includes('street_number'));
                        if (hasRoute || hasStreetNumber) {
                            return false;
                        }

                        const locality = componentText(components, 'locality').trim();
                        const formatted = (result.formatted_address || '').trim().toLowerCase();
                        const localityIsRuse = /^(русе|ruse)\b/.test(locality);
                        const formattedIsRuse = /^(русе|ruse),?\s*българия$/i.test(formatted)
                            || /^(русе|ruse),?\s*bulgaria$/i.test(formatted);

                        return localityIsRuse || formattedIsRuse;
                    }

                    function isPreciseEnough(result, query) {
                        const components = result.address_components || [];
                        const hasRoute = components.some((c) => c.types.includes('route'));
                        const hasStreetNumber = components.some((c) => c.types.includes('street_number'));
                        const q = (query || '').trim();

                        if (hasRoute || hasStreetNumber) {
                            return true;
                        }

                        // Не връщай центъра на гр. Русе при търсене на друго място.
                        if (isBareRuseCity(result) && !/^(русе|ruse)$/i.test(q)) {
                            return false;
                        }

                        const rank = resultRank(result);
                        const types = result.types || [];

                        if (queryWantsStreet(q)) {
                            return rank <= 1;
                        }

                        // Име на село/квартал — приемай locality и neighborhood в областта.
                        if (rank <= 2 || rank === 4) {
                            return true;
                        }

                        // Отхвърли само „Област Русе“ като цяло.
                        if (types.includes('administrative_area_level_1') && !types.includes('locality')) {
                            return false;
                        }

                        return rank < 5;
                    }

                    function pickRuseAddress(results, query) {
                        const candidates = (results || [])
                            .filter(isInRuseRegion)
                            .filter((result) => isPreciseEnough(result, query));

                        if (!candidates.length) {
                            return null;
                        }

                        candidates.sort((a, b) => {
                            const rankDiff = resultRank(a) - resultRank(b);
                            if (rankDiff !== 0) {
                                return rankDiff;
                            }

                            const aPos = latLngOf(a.geometry.location);
                            const bPos = latLngOf(b.geometry.location);
                            return distanceKm(storeLat, storeLng, aPos.lat, aPos.lng)
                                - distanceKm(storeLat, storeLng, bPos.lat, bPos.lng);
                        });

                        return candidates[0];
                    }

                    function applyAddressMatch(match) {
                        const { lat, lng } = latLngOf(match.geometry.location);
                        deliveryMarker.setVisible(true);
                        setPoint(lat, lng, true);
                        // Ако setPoint е отхвърлил точката (извън радиус), не презаписвай адреса.
                        if (latField.value) {
                            addressField.value = match.formatted_address || match.name || '';
                        }
                    }

                    function geocodeInRuse(addressQuery, queryForPrecision, done) {
                        geocoder.geocode({
                            address: addressQuery,
                            componentRestrictions: { country: 'BG' },
                            bounds: ruseBounds,
                            region: 'bg',
                        }, (results, status) => {
                            if (status !== 'OK' || !results?.length) {
                                done(null);
                                return;
                            }

                            done(pickRuseAddress(results, queryForPrecision));
                        });
                    }

                    function findPlaceInRuse(query, done) {
                        if (!placesService) {
                            done(null);
                            return;
                        }

                        const placeQuery = queryWantsStreet(query)
                            ? `${/^(ул\.?|улица)\s/i.test(query) ? query : `ул. ${query}`}, Русе, България`
                            : `${query}, област Русе, България`;

                        placesService.findPlaceFromQuery({
                            query: placeQuery,
                            fields: ['formatted_address', 'geometry', 'name', 'types', 'place_id'],
                            locationBias: ruseBounds,
                            language: 'bg',
                        }, (results, status) => {
                            if (status !== google.maps.places.PlacesServiceStatus.OK || !results?.length) {
                                done(null);
                                return;
                            }

                            const placeId = results[0].place_id;
                            if (!placeId) {
                                done(pickRuseAddress(results, query));
                                return;
                            }

                            placesService.getDetails({
                                placeId,
                                fields: ['formatted_address', 'geometry', 'name', 'types', 'address_components'],
                                language: 'bg',
                            }, (place, detailStatus) => {
                                if (detailStatus !== google.maps.places.PlacesServiceStatus.OK || !place) {
                                    done(null);
                                    return;
                                }

                                if (!isInRuseRegion(place) || !isPreciseEnough(place, query)) {
                                    done(null);
                                    return;
                                }

                                done(place);
                            });
                        });
                    }

                    function searchDeliveryAddress() {
                        const q = searchInput.value.trim();
                        if (!q) {
                            return;
                        }

                        const queries = [];
                        const wantsStreet = queryWantsStreet(q);
                        const hasStreetPrefix = /^(ул\.?|улица|бул\.?|булевард)\s/i.test(q);
                        const alreadyMentionsRuse = /русе|ruse/i.test(q);

                        if (wantsStreet || hasStreetPrefix) {
                            if (hasStreetPrefix) {
                                queries.push(alreadyMentionsRuse ? `${q}, България` : `${q}, Русе, България`);
                                queries.push(`${q}, 7000 Русе, България`);
                            } else {
                                queries.push(`ул. ${q}, Русе, България`);
                                queries.push(`улица ${q}, Русе, България`);
                                queries.push(`ул. ${q}, 7000 Русе, България`);
                                queries.push(alreadyMentionsRuse ? `${q}, България` : `${q}, Русе, България`);
                            }
                        } else {
                            queries.push(`${q}, област Русе, България`);
                            queries.push(`${q}, Русе, България`);
                            if (!alreadyMentionsRuse) {
                                queries.push(`${q}, България`);
                            }
                        }

                        const tryNext = (index) => {
                            if (index >= queries.length) {
                                findPlaceInRuse(q, (placeMatch) => {
                                    if (!placeMatch) {
                                        showStatusMessage('Не открихме този адрес в област Русе. Изберете от предложенията или кликнете на картата.', true);
                                        return;
                                    }
                                    applyAddressMatch(placeMatch);
                                });
                                return;
                            }

                            geocodeInRuse(queries[index], q, (match) => {
                                if (match) {
                                    applyAddressMatch(match);
                                    return;
                                }
                                tryNext(index + 1);
                            });
                        };

                        tryNext(0);
                    }

                    let placeJustSelected = false;

                    if (google.maps.places?.Autocomplete) {
                        const autocomplete = new google.maps.places.Autocomplete(searchInput, {
                            componentRestrictions: { country: 'bg' },
                            fields: ['formatted_address', 'geometry', 'name', 'types', 'address_components'],
                            bounds: ruseBounds,
                            strictBounds: true,
                        });
                        autocomplete.bindTo('bounds', mapInstance);

                        autocomplete.addListener('place_changed', () => {
                            placeJustSelected = true;
                            window.setTimeout(() => { placeJustSelected = false; }, 500);

                            const place = autocomplete.getPlace();
                            if (!place?.geometry?.location) {
                                showStatusMessage('Изберете адрес от списъка с предложения.', true);
                                return;
                            }

                            if (!isInRuseRegion(place)) {
                                showStatusMessage('Адресът е извън област Русе. Моля, изберете адрес в област Русе.', true);
                                return;
                            }

                            if (!isPreciseEnough(place, searchInput.value.trim() || place.formatted_address || '')) {
                                showStatusMessage('Моля, изберете по-точен адрес (улица и номер или населено място).', true);
                                return;
                            }

                            applyAddressMatch(place);
                        });
                    }

                    document.getElementById('address-search-btn').addEventListener('click', searchDeliveryAddress);

                    searchInput.addEventListener('keydown', (e) => {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            window.setTimeout(() => {
                                if (!placeJustSelected) {
                                    searchDeliveryAddress();
                                }
                            }, 300);
                        }
                    });

                    if (savedSelect) {
                        savedSelect.addEventListener('change', () => {
                            const opt = savedSelect.selectedOptions[0];
                            if (savedSelect.value) addressField.value = savedSelect.value;
                            if (opt && opt.dataset.lat && opt.dataset.lng) {
                                deliveryMarker.setVisible(true);
                                setPoint(parseFloat(opt.dataset.lat), parseFloat(opt.dataset.lng), true);
                            }
                        });
                    }

                    const oldLat = parseFloat(mapEl.dataset.oldLat);
                    const oldLng = parseFloat(mapEl.dataset.oldLng);
                    if (!isNaN(oldLat) && !isNaN(oldLng)) {
                        deliveryMarker.setVisible(true);
                        setPoint(oldLat, oldLng, true);
                    }

                    updateTotals();
                }

                function reverseGeocode(lat, lng) {
                    if (typeof google === 'undefined' || !google.maps) return;
                    const geocoder = new google.maps.Geocoder();
                    geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                        if (status === 'OK' && results?.length) {
                            addressField.value = results[0].formatted_address;
                        }
                    });
                }

                window.initCheckoutGoogleMap = function () {
                    initGoogleMap();
                };

                window.handleCheckoutGoogleMapsError = function () {
                    updateTotals();
                    showMapMessage('Google Maps не се зареди. Проверете API ключа и разрешените домейни в Google Cloud.');
                };

                window.gm_authFailure = function () {
                    updateTotals();
                    showMapMessage('Google Maps API ключът не е разрешен за този домейн или API услугата не е активирана.');
                };

                if (!hasGoogleMapsKey) {
                    updateTotals();
                    showMapMessage('Липсва GOOGLE_MAPS_API_KEY в конфигурацията.');
                }
            })();
        </script>
        @if ($googleMapsKey)
            <script
                src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsKey }}&libraries=places&language=bg&region=BG&callback=initCheckoutGoogleMap&loading=async&auth_referrer_policy=origin"
                async
                defer
                onerror="window.handleCheckoutGoogleMapsError && window.handleCheckoutGoogleMapsError()"></script>
        @endif
    @endpush
@endsection
