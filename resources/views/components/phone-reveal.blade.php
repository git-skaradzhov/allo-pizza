@props([
    'variant' => 'header',
    'numberClass' => null,
])

@php
    use App\Models\StoreSetting;

    $storeSetting = $storeSetting ?? StoreSetting::current();
    $phones = $storeSetting->phoneNumbers();
    $id = 'phone-reveal-'.\Illuminate\Support\Str::random(8);
    $numberClass ??= match ($variant) {
        'header-mobile' => 'inline-flex items-center gap-1 whitespace-nowrap text-xs font-bold leading-tight text-brand-600 transition hover:text-brand-700 sm:text-sm',
        'header' => 'inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-bold leading-tight text-brand-600 transition hover:bg-brand-50',
        'menu' => 'flex items-center gap-2 rounded-lg py-2 text-lg font-bold text-brand-400 transition hover:text-brand-300',
        default => 'font-bold text-brand-600 hover:text-brand-700',
    };
    $numbersDisplay = match ($variant) {
        'footer' => 'row',
        'menu' => 'stack',
        default => 'col',
    };
@endphp

@once
    <script>
        try {
            if (sessionStorage.getItem('allo_phone_revealed') === '1') {
                document.documentElement.classList.add('phones-revealed');
            }
        } catch (e) {}
    </script>
    <style>
        html.phones-revealed [data-phone-reveal-trigger] { display: none !important; }
        html.phones-revealed [data-phone-reveal-numbers][data-display="col"] { display: flex !important; flex-direction: column !important; }
        html.phones-revealed [data-phone-reveal-numbers][data-display="row"] { display: flex !important; flex-wrap: wrap !important; }
        html.phones-revealed [data-phone-reveal-numbers][data-display="stack"] { display: block !important; }
    </style>
@endonce

@if ($phones)
    <div {{ $attributes->class(['phone-reveal', 'inline-flex' => $variant === 'footer']) }} data-phone-reveal>
        <button
            type="button"
            id="{{ $id }}-trigger"
            data-phone-reveal-trigger
            aria-expanded="false"
            aria-controls="{{ $id }}-numbers"
            @class([
                'inline-flex items-center gap-1.5 font-bold transition',
                'rounded-lg bg-brand-500 px-2 py-1.5 text-left text-[11px] leading-tight text-white shadow-sm hover:bg-brand-600 sm:px-2.5 sm:text-xs' => $variant === 'header-mobile',
                'rounded-lg bg-brand-500 px-3 py-2 text-sm text-white shadow-sm hover:bg-brand-600' => $variant === 'header',
                'mt-1 w-full justify-center rounded-xl bg-brand-500 px-3 py-2.5 text-sm text-white hover:bg-brand-600' => $variant === 'menu',
                'text-brand-600 hover:text-brand-700' => $variant === 'footer',
            ])
        >
            <svg class="{{ $variant === 'menu' ? 'h-4 w-4' : 'h-3.5 w-3.5' }} shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
            </svg>
            <span>Покажи телефоните</span>
        </button>

        <div
            id="{{ $id }}-numbers"
            data-phone-reveal-numbers
            data-display="{{ $numbersDisplay }}"
            hidden
            @class([
                'hidden',
                'min-w-0 flex-col gap-0.5' => $variant === 'header-mobile' || $variant === 'header',
                'flex-wrap items-center gap-x-3 gap-y-1' => $variant === 'footer',
                'mt-1 space-y-1 pl-1' => $variant === 'menu',
            ])
        >
            @foreach ($phones as $phone)
                <a href="tel:{{ StoreSetting::normalizePhone($phone) }}" class="{{ $numberClass }}">
                    @if (in_array($variant, ['header-mobile', 'header', 'menu'], true))
                        <svg class="{{ $variant === 'header-mobile' ? 'h-3 w-3 sm:h-3.5 sm:w-3.5' : 'h-3.5 w-3.5' }} shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                    @endif
                    {{ $phone }}
                </a>
            @endforeach
        </div>
    </div>
@endif
