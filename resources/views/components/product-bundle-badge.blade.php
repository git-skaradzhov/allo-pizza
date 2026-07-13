@props(['label' => null])

@php
    $label ??= config('promotions.pizza_bundle.label', '4+1');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center rounded-full bg-green-600 px-1.5 py-0.5 text-[10px] font-extrabold leading-none text-white sm:px-2 sm:text-xs']) }}>
    {{ $label }}
</span>
