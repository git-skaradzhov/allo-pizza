@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $pageTitle = $page?->title ?? 'Обедно меню';
    $seo = app(SeoBuilder::class)->forLunch($page);
@endphp

    $dayLabels = [
        1 => 'Пон',
        2 => 'Вто',
        3 => 'Сря',
        4 => 'Чет',
        5 => 'Пет',
        6 => 'Съб',
        7 => 'Нед',
    ];
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => $pageTitle],
    ]" />

    <article class="overflow-hidden rounded-[1.5rem] border border-stone-200 bg-white shadow-soft sm:rounded-[2rem]">
        <div class="border-b border-stone-100 bg-gradient-to-br from-gold-500/15 via-white to-brand-50 px-4 py-6 sm:px-8 sm:py-8">
            <p class="text-sm font-bold text-brand-600">Акция</p>
            <h1 class="mt-1 text-3xl font-black tracking-tight text-stone-950 sm:text-4xl">{{ $pageTitle }}</h1>

            @if ($page?->content)
                <div class="prose prose-stone mt-5 max-w-none text-sm leading-7 sm:text-base [&_a]:font-bold [&_a]:text-brand-600 [&_p:last-child]:mb-0">
                    {!! $page->content !!}
                </div>
            @else
                <p class="mt-4 max-w-3xl text-sm leading-7 text-stone-700 sm:text-base">
                    В Allo! Pizza можете да поръчате полноценен обяд на специална цена: пица, пърленка, салата или десерт.
                </p>
            @endif
        </div>

        <div class="space-y-8 px-4 py-6 sm:px-8 sm:py-8">
            @forelse ($lunchMenus as $lunchMenu)
                @php
                    $isActiveNow = $lunchMenu->isCurrentlyActive();
                    $days = collect($lunchMenu->days_of_week ?? [])
                        ->map(fn ($day) => $dayLabels[(int) $day] ?? $day)
                        ->implode(', ');
                    [$leftMealColumns, $rightMealColumns] = $lunchMenu->mealOrderColumns();
                    $mealSectionGroups = $lunchMenu->itemsGroupedByMealOrder();
                @endphp

                <section class="rounded-[1.25rem] border border-stone-200 bg-stone-50/70 p-4 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-extrabold tracking-tight text-stone-950">{{ $lunchMenu->title }}</h2>
                                @if ($isActiveNow)
                                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">Активно сега</span>
                                @else
                                    <span class="inline-flex rounded-full bg-stone-200 px-3 py-1 text-xs font-bold text-stone-600">Извън обедните часове</span>
                                @endif
                            </div>

                            @if ($lunchMenu->description)
                                <p class="mt-2 max-w-3xl text-sm text-stone-600 sm:text-base">{{ $lunchMenu->description }}</p>
                            @endif

                            @if ($lunchMenu->message)
                                <p class="mt-3 rounded-2xl bg-white px-4 py-3 text-sm font-medium text-brand-700 shadow-soft">
                                    {{ $lunchMenu->message }}
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0 rounded-2xl bg-white px-4 py-3 text-sm shadow-soft">
                            <p class="font-bold text-stone-900">{{ substr($lunchMenu->start_time, 0, 5) }} – {{ substr($lunchMenu->end_time, 0, 5) }} ч.</p>
                            @if ($days)
                                <p class="mt-1 text-stone-500">{{ $days }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($mealSectionGroups->isNotEmpty())
                        <form method="POST" action="{{ route('lunch.add-selected') }}" id="bulk-lunch-form-{{ $lunchMenu->id }}" class="hidden" aria-hidden="true">
                            @csrf
                        </form>

                        <div class="mt-6">
                            <div class="grid gap-5 lg:grid-cols-2">
                                @foreach ([$leftMealColumns, $rightMealColumns] as $columnGroups)
                                    <div class="space-y-5">
                                        @foreach ($columnGroups as $sectionName => $items)
                                    <section class="overflow-hidden rounded-[1.35rem] border border-stone-200 bg-white shadow-soft">
                                        <div class="flex items-center justify-between gap-3 border-b border-stone-100 bg-gradient-to-r from-stone-50 to-white px-4 py-3">
                                            <h3 class="text-lg font-black tracking-tight text-stone-950">{{ $sectionName }}</h3>
                                            <span class="rounded-full bg-gold-500/15 px-3 py-1 text-xs font-bold text-brand-700">
                                                {{ $items->count() }} предложения
                                            </span>
                                        </div>

                                        <div class="divide-y divide-stone-100">
                                            @foreach ($items as $item)
                                                @php
                                                    $image = $item->image ? Storage::url($item->image) : null;
                                                @endphp

                                                <div class="grid gap-3 p-3 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center sm:p-4">
                                                    <label class="flex items-start gap-3 sm:contents">
                                                        <input type="checkbox"
                                                               name="selected[]"
                                                               value="{{ $item->id }}"
                                                               form="bulk-lunch-form-{{ $lunchMenu->id }}"
                                                               class="mt-1 h-4 w-4 rounded border-stone-300 text-brand-500 focus:ring-brand-400 sm:mt-0"
                                                               @disabled(! $isActiveNow)>

                                                        <div class="grid min-w-0 grid-cols-[60px_1fr] gap-3 sm:col-span-1 sm:grid-cols-[68px_1fr]">
                                                            <span class="flex h-[60px] w-[60px] items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-gold-300/35 to-brand-100 text-2xl sm:h-[68px] sm:w-[68px]">
                                                                @if ($image)
                                                                    <img src="{{ $image }}" alt="{{ $item->name }}" loading="lazy" class="h-full w-full object-cover">
                                                                @else
                                                                    {{ $item->fallbackIcon() }}
                                                                @endif
                                                            </span>

                                                            <span class="min-w-0">
                                                                <span class="flex flex-wrap items-center gap-1.5">
                                                                    <span class="text-sm font-extrabold leading-tight text-stone-950 sm:text-base">{{ $item->name }}</span>
                                                                    @if ($item->is_new)
                                                                        <span class="rounded-full bg-gold-500 px-2 py-0.5 text-[10px] font-bold text-brand-900">Ново</span>
                                                                    @endif
                                                                    @if ($item->is_hit)
                                                                        <span class="rounded-full bg-brand-500 px-2 py-0.5 text-[10px] font-bold text-white">Хит</span>
                                                                    @endif
                                                                    @if ($item->is_spicy)
                                                                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-600 ring-1 ring-brand-100">🌶 Люто</span>
                                                                    @endif
                                                                </span>

                                                                @if ($item->description)
                                                                    <span class="mt-1 line-clamp-2 block text-xs leading-5 text-stone-500 sm:text-sm">
                                                                        {{ $item->description }}
                                                                    </span>
                                                                @endif
                                                            </span>
                                                        </div>
                                                    </label>

                                                    <div class="flex flex-col items-stretch gap-2 sm:min-w-[112px] sm:items-end">
                                                        <span class="text-base font-black text-stone-950 sm:text-right">{{ money((float) $item->price) }}</span>

                                                        <form method="POST" action="{{ route('lunch.items.add', $item) }}" class="flex items-center gap-2 sm:justify-end">
                                                            @csrf
                                                            <input type="number"
                                                                   name="quantity"
                                                                   value="1"
                                                                   min="1"
                                                                   max="20"
                                                                   class="w-14 rounded-xl border-stone-300 px-2 py-1.5 text-center text-sm focus:border-brand-400 focus:ring-brand-400"
                                                                   oninput="document.getElementById('bulk-qty-{{ $lunchMenu->id }}-{{ $item->id }}').value = this.value"
                                                                   @disabled(! $isActiveNow)>
                                                            <button type="submit"
                                                                    class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-xs font-extrabold text-white shadow-soft transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-stone-300"
                                                                    @disabled(! $isActiveNow)>
                                                                Добави
                                                            </button>
                                                        </form>

                                                        <input type="number"
                                                               id="bulk-qty-{{ $lunchMenu->id }}-{{ $item->id }}"
                                                               name="quantities[{{ $item->id }}]"
                                                               value="1"
                                                               min="1"
                                                               max="20"
                                                               form="bulk-lunch-form-{{ $lunchMenu->id }}"
                                                               class="hidden"
                                                               @disabled(! $isActiveNow)>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-stone-500">Изберете един или повече артикула и ги добавете директно в количката.</p>
                                <button type="submit"
                                        form="bulk-lunch-form-{{ $lunchMenu->id }}"
                                        class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-6 py-3 text-sm font-extrabold text-white shadow-soft transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-stone-300"
                                        @disabled(! $isActiveNow)>
                                    Добави избраните
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="mt-6 text-sm text-stone-500">Няма добавени артикули към това обедно меню.</p>
                    @endif
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-300 bg-stone-50 px-6 py-10 text-center">
                    <p class="text-stone-600">В момента няма активно обедно меню.</p>
                    <a href="{{ route('menu') }}" class="mt-4 inline-block rounded-xl bg-brand-500 px-6 py-3 font-bold text-white hover:bg-brand-600">
                        Към пълното меню
                    </a>
                </div>
            @endforelse
        </div>
    </article>
@endsection
