@props([
    'currentLabel',
    'oldLabel' => null,
    'savings' => null,
    'savingsMax' => null,
    'compact' => false,
])

@php
    $hasDiscount = $oldLabel !== null && $savings !== null && $savings > 0;
    $savingsLabel = $hasDiscount
        ? ($savingsMax !== null && $savingsMax > $savings
            ? 'Спестяваш до '.money($savingsMax)
            : 'Спестяваш '.money($savings))
        : null;
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-0.5']) }}>
    @if ($hasDiscount)
        <div class="flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5">
            <span @class([
                'font-medium text-stone-400 line-through',
                'text-[11px] sm:text-xs' => $compact,
                'text-sm' => ! $compact,
            ])>{{ $oldLabel }}</span>
            <span @class([
                'font-bold text-brand-600',
                'text-xs sm:text-sm' => $compact,
                'text-lg sm:text-xl' => ! $compact,
            ])>{{ $currentLabel }}</span>
        </div>
        @if ($savingsLabel)
            <span @class([
                'font-semibold text-green-700',
                'text-[10px] sm:text-xs' => $compact,
                'text-sm' => ! $compact,
            ])>{{ $savingsLabel }}</span>
        @endif
    @else
        <span @class([
            'font-bold text-stone-900',
            'text-xs leading-tight sm:text-sm' => $compact,
            'text-lg sm:text-xl' => ! $compact,
        ])>{{ $currentLabel }}</span>
    @endif
</div>
