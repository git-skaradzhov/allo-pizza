@props([
    'class' => 'h-9 w-auto max-w-[148px] object-contain sm:h-10 sm:max-w-[180px] md:h-11 md:max-w-[220px]',
])

@php
    $alt = $storeSetting->store_name ?? 'Allo! Pizza';
@endphp

<picture>
    <source type="image/webp" srcset="{{ asset('images/logo-wide.webp') }}">
    <img src="{{ asset('images/logo-wide.png') }}"
         alt="{{ $alt }}"
         width="220"
         height="80"
         decoding="async"
         {{ $attributes->merge(['class' => $class]) }}>
</picture>
