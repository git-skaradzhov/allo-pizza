@php
    $policySlug = config('cookies.policy_slug', 'politika-za-biskvitki');
    $policyUrl = route('pages.show', $policySlug);
@endphp

<div
    id="cookie-consent-banner"
    class="fixed inset-x-0 bottom-0 z-50 hidden border-t border-stone-200 bg-white p-4 shadow-[0_-4px_24px_rgba(0,0,0,0.08)] print:hidden sm:p-5"
    role="dialog"
    aria-labelledby="cookie-consent-title"
    aria-describedby="cookie-consent-description"
    aria-hidden="true"
>
    <div class="mx-auto flex max-w-7xl flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-3xl">
            <h2 id="cookie-consent-title" class="text-base font-bold text-stone-900 sm:text-lg">
                Бисквитки и поверителност
            </h2>
            <p id="cookie-consent-description" class="mt-1 text-sm leading-relaxed text-stone-600">
                Използваме необходими бисквитки, за да работи сайтът. С ваше съгласие използваме и аналитични
                и маркетингови бисквитки, за да подобрим услугата и да измерим ефективността на рекламите.
                Можете да промените избора си по всяко време.
                <a href="{{ $policyUrl }}" class="font-medium text-brand-600 underline hover:text-brand-700">
                    Политика за бисквитки
                </a>
            </p>
        </div>

        <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:flex-wrap lg:justify-end">
            <button
                type="button"
                id="cookie-consent-reject-all"
                class="inline-flex items-center justify-center rounded-xl border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
            >
                Само необходими
            </button>
            <button
                type="button"
                id="cookie-consent-open-settings"
                class="inline-flex items-center justify-center rounded-xl border border-stone-300 bg-white px-4 py-2.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
            >
                Настройки
            </button>
            <button
                type="button"
                id="cookie-consent-accept-all"
                class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-600"
            >
                Приемам всички
            </button>
        </div>
    </div>
</div>

<div
    id="cookie-consent-settings"
    class="fixed inset-0 z-[60] hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="cookie-consent-settings-title"
    aria-hidden="true"
>
    <div
        id="cookie-consent-settings-backdrop"
        class="absolute inset-0 bg-black/50"
    ></div>

    <div class="absolute inset-x-4 top-1/2 mx-auto max-h-[90vh] max-w-lg -translate-y-1/2 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl sm:inset-x-auto">
        <div class="flex items-start justify-between gap-4">
            <h2 id="cookie-consent-settings-title" class="text-lg font-bold text-stone-900">
                Настройки за бисквитки
            </h2>
            <button
                type="button"
                id="cookie-consent-close-settings"
                class="rounded-lg p-1 text-stone-500 transition hover:bg-stone-100 hover:text-stone-700"
                aria-label="Затвори"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <p class="mt-2 text-sm text-stone-600">
            Изберете кои категории бисквитки да разрешите. Необходимите бисквитки не могат да бъдат изключени.
        </p>

        <div class="mt-6 space-y-4">
            <div class="flex items-start justify-between gap-4 rounded-xl border border-stone-200 bg-stone-50 p-4">
                <div>
                    <p class="font-semibold text-stone-900">Необходими</p>
                    <p class="mt-1 text-sm text-stone-600">
                        Нужни за работата на сайта — сесия, количка и сигурност.
                    </p>
                </div>
                <span class="shrink-0 rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-600">
                    Винаги активни
                </span>
            </div>

            <div class="flex items-start justify-between gap-4 rounded-xl border border-stone-200 p-4">
                <div>
                    <label for="cookie-toggle-analytics" class="font-semibold text-stone-900">Аналитика</label>
                    <p class="mt-1 text-sm text-stone-600">
                        Google Analytics и Tag Manager — помагат ни да разберем как се използва сайтът.
                    </p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input
                        type="checkbox"
                        id="cookie-toggle-analytics"
                        data-cookie-toggle="analytics"
                        class="peer sr-only"
                        role="switch"
                        aria-checked="false"
                    >
                    <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </label>
            </div>

            <div class="flex items-start justify-between gap-4 rounded-xl border border-stone-200 p-4">
                <div>
                    <label for="cookie-toggle-marketing" class="font-semibold text-stone-900">Маркетинг</label>
                    <p class="mt-1 text-sm text-stone-600">
                        Meta Pixel — измерване на рекламни кампании и персонализирана реклама.
                    </p>
                </div>
                <label class="relative inline-flex shrink-0 cursor-pointer items-center">
                    <input
                        type="checkbox"
                        id="cookie-toggle-marketing"
                        data-cookie-toggle="marketing"
                        class="peer sr-only"
                        role="switch"
                        aria-checked="false"
                    >
                    <span class="h-6 w-11 rounded-full bg-stone-300 transition peer-checked:bg-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
            <a
                href="{{ $policyUrl }}"
                class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-brand-600 transition hover:text-brand-700"
            >
                Политика за бисквитки
            </a>
            <button
                type="button"
                id="cookie-consent-save-settings"
                class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-soft transition hover:bg-brand-600"
            >
                Запази избора
            </button>
        </div>
    </div>
</div>
