@props([
    'variant' => 'card',
])

@php
    $summary = $weeklyWorkingHoursSummary ?? app(\App\Services\StoreService::class)->weeklyScheduleSummary();
    $todayMessage = $workingHoursMessage ?? app(\App\Services\StoreService::class)->workingHoursMessage();
@endphp

@if ($variant === 'card')
    <a href="{{ route('pages.show', 'kontakti') }}"
       style="width: 200px; max-width: 200px; height: 260px;"
       class="group relative flex shrink-0 snap-start overflow-hidden rounded-[1.35rem] bg-gradient-to-br from-stone-800 to-stone-950 p-4 shadow-soft transition hover:-translate-y-0.5 hover:shadow-card sm:rounded-[1.65rem]">
        <span class="absolute right-2 top-2 z-0 text-5xl font-black leading-none text-white/20 sm:text-6xl">🕘</span>
        <div class="relative z-10 flex h-full flex-col justify-end text-white">
            <p class="text-xs font-bold uppercase tracking-wide text-gold-300">Работно време</p>
            <p class="mt-2 text-lg font-black leading-tight">{{ $todayMessage }}</p>
            <p class="mt-2 text-sm leading-snug text-white/75">{{ $summary }}</p>
        </div>
    </a>
@else
    <p class="text-sm text-stone-600">{{ $summary }}</p>
@endif
