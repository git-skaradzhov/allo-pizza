@php
    use App\Services\DeliveryService;

    $deliveryService = app(DeliveryService::class);
    $zonePolygon = $deliveryService->zonePolygon();
    $insidePrice = (float) ($storeSetting->delivery_inside_price ?? 2);
    $outsidePrice = (float) ($storeSetting->delivery_outside_price ?? 3);
    $storeLat = (float) ($storeSetting->store_lat ?? 43.8407475);
    $storeLng = (float) ($storeSetting->store_lng ?? 25.9549665);
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
        id="delivery-zone-page-map"
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

    <p id="delivery-zone-page-status" class="hidden text-sm"></p>
</div>
