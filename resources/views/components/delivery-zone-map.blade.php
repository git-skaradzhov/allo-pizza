@props([
    'variant' => 'hero',
    'showLegend' => true,
    'interactive' => true,
])

@php
    use App\Services\DeliveryService;

    $deliveryService = app(DeliveryService::class);
    $zonePolygon = $deliveryService->zonePolygon();
    $insidePrice = (float) ($storeSetting->delivery_inside_price ?? 2);
    $outsidePrice = (float) ($storeSetting->delivery_outside_price ?? 3);
    $storeLat = (float) ($storeSetting->store_lat ?? 43.8407475);
    $storeLng = (float) ($storeSetting->store_lng ?? 25.9549665);

    $mapHeightClass = $variant === 'hero'
        ? 'h-[280px] sm:h-[360px] lg:h-[420px]'
        : 'h-72 sm:h-80 lg:h-[360px]';
@endphp

<div {{ $attributes->class(['space-y-4']) }}>
    @if ($variant !== 'hero' && $showLegend)
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
    @endif

    <div
        id="delivery-zone-page-map"
        class="{{ $mapHeightClass }} w-full overflow-hidden rounded-2xl border border-stone-200 bg-stone-100"
        data-store-lat="{{ $storeLat }}"
        data-store-lng="{{ $storeLng }}"
        data-store-logo="{{ asset('images/logo-map.png') }}"
        data-inside-price="{{ $insidePrice }}"
        data-outside-price="{{ $outsidePrice }}"
        data-interactive="{{ $interactive ? '1' : '0' }}"
        data-polygon='@json($zonePolygon)'
    ></div>

    @if ($variant === 'hero' && $showLegend)
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
    @endif

    @if ($interactive)
        <p id="delivery-zone-page-status" class="hidden text-sm text-stone-600"></p>
        <p class="text-sm text-stone-500">Кликнете на картата, за да проверите дали адресът ви попада в района.</p>
    @endif
</div>
