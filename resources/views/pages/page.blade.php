@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPage($page, route('pages.show', $page->slug));
@endphp

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;

        $featuredImage = $page->featured_image ? Storage::url($page->featured_image) : null;
    @endphp
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => $page->title],
    ]" />

    @if ($featuredImage)
        <div class="mb-6 overflow-hidden rounded-[1.5rem] shadow-soft sm:rounded-[2rem]">
            <img src="{{ $featuredImage }}" alt="{{ $page->title }}" class="aspect-[21/9] w-full object-cover">
        </div>
    @endif

    <h1 class="mb-6 text-3xl font-bold">{{ $page->title }}</h1>

    @if ($page->slug === 'kontakti')
        <div class="grid gap-6 lg:grid-cols-2 lg:items-stretch lg:gap-8">
            <div class="prose min-h-[280px] max-w-none rounded-2xl border border-stone-200 bg-white p-6 lg:min-h-[360px]">
                {!! $page->content !!}
            </div>
            <div class="min-h-[280px] overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-soft lg:min-h-[360px]">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d719.4319000327891!2d25.955713585583943!3d43.8407457680951!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x40ae60bf8b946307%3A0xcfe9be56ae94a509!2z0LYu0LouINCl0YrRiNC-0LLQtSwg0YPQuy4g4oCe0JzQsNGA0LjRjyDQm9GD0LjQt9Cw4oCcIDIyLCA3MDEyINCg0YPRgdC1!5e0!3m2!1sbg!2sbg!4v1782300298988!5m2!1sbg!2sbg"
                    class="h-full min-h-[280px] w-full border-0 lg:min-h-[360px]"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    title="Allo! Pizza на карта"></iframe>
            </div>
        </div>
    @elseif ($page->slug === 'dostavka')
        <div class="grid gap-6 lg:grid-cols-2 lg:items-start lg:gap-8">
            <div class="prose max-w-none rounded-2xl border border-stone-200 bg-white p-6">
                {!! $page->content !!}

                <ul class="not-prose mt-6 space-y-2 text-sm text-stone-700">
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                        <span><strong>{{ money((float) ($storeSetting->delivery_inside_price ?? 2)) }}</strong> — доставка в района (очертан на картата)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-stone-400"></span>
                        <span><strong>{{ money((float) ($storeSetting->delivery_outside_price ?? 3)) }}</strong> — доставка извън района</span>
                    </li>
                    @if ($storeSetting->free_delivery_over)
                        <li class="flex items-start gap-2">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-green-500"></span>
                            <span>Безплатна доставка при поръчка над {{ money((float) $storeSetting->free_delivery_over) }}</span>
                        </li>
                    @endif
                    <li class="flex items-start gap-2">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-gold-500"></span>
                        <span>Средно време за доставка: до {{ $storeSetting->average_delivery_time ?? 30 }} мин.</span>
                    </li>
                </ul>

                <p class="not-prose mt-4 text-sm text-stone-500">
                    {{ \App\Support\DeliveryZone::boundaryDescription() }}
                </p>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-soft sm:p-6">
                <h2 class="mb-4 text-lg font-extrabold text-stone-900">Зона за доставка</h2>
                <p class="mb-4 text-sm text-stone-500">Кликнете на картата, за да проверите дали адресът ви попада в района.</p>
                <x-delivery-zone-map />
            </div>
        </div>
    @else
        <div class="prose max-w-none rounded-2xl border border-stone-200 bg-white p-6">
            {!! $page->content !!}
        </div>
    @endif

    @if (! empty($galleryImages))
        <section class="mt-8">
            <h2 class="mb-4 text-xl font-extrabold tracking-tight text-stone-900">Заведението</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                @foreach ($galleryImages as $image)
                    <div class="aspect-square overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-soft">
                        <img src="{{ $image }}" alt="Allo! Pizza" loading="lazy"
                             class="h-full w-full object-cover transition duration-300 hover:scale-105">
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
