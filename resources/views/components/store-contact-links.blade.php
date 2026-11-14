@props([
    'linkClass' => 'font-bold text-brand-600 hover:text-brand-700',
    'stacked' => false,
])

@php
    $storeSetting = $storeSetting ?? \App\Models\StoreSetting::current();
@endphp

@if ($stacked)
    <div {{ $attributes->class(['space-y-1']) }}>
        <x-phone-reveal variant="footer" :number-class="$linkClass.' block'" />

        @if (! empty($storeSetting?->store_email))
            <a href="mailto:{{ $storeSetting->store_email }}" @class([$linkClass, 'block'])>
                {{ $storeSetting->store_email }}
            </a>
        @endif
    </div>
@else
    <div {{ $attributes->class(['flex flex-wrap items-center gap-x-3 gap-y-1']) }}>
        <x-phone-reveal variant="footer" :number-class="$linkClass" />

        @if (! empty($storeSetting?->store_email))
            <a href="mailto:{{ $storeSetting->store_email }}" class="{{ $linkClass }}">
                {{ $storeSetting->store_email }}
            </a>
        @endif
    </div>
@endif
