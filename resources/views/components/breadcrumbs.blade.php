@props(['items' => [], 'variant' => 'light'])

@php
    $navClass = $variant === 'inverted'
        ? 'mb-4 text-sm text-white/70 sm:mb-6'
        : 'mb-4 text-sm text-stone-500 sm:mb-6';
    $linkClass = $variant === 'inverted'
        ? 'text-white/80 hover:text-white'
        : 'hover:text-brand-600';
    $currentClass = $variant === 'inverted'
        ? 'text-white'
        : 'text-stone-800';
@endphp

@if (! empty($items))
    <nav {{ $attributes->merge(['class' => $navClass]) }} aria-label="Breadcrumb">
        @foreach ($items as $index => $item)
            @if ($index > 0)
                <span class="mx-2 opacity-60">/</span>
            @endif

            @if (! empty($item['url']) && $index < count($items) - 1)
                <a href="{{ $item['url'] }}" class="{{ $linkClass }}">{{ $item['label'] }}</a>
            @else
                <span @class([$currentClass => $index === count($items) - 1])>{{ $item['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
