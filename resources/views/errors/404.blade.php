@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forNotFound();
    $redirectAfterSeconds = 10;
@endphp

@section('full')
    <section class="relative overflow-hidden bg-gradient-to-b from-brand-50 via-stone-50 to-white">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <span class="absolute left-[8%] top-14 h-24 w-24 rounded-full bg-gold-400/25 blur-2xl"></span>
            <span class="absolute right-[10%] top-32 h-36 w-36 rounded-full bg-brand-300/25 blur-3xl"></span>
            <span class="absolute bottom-10 left-1/2 h-44 w-44 -translate-x-1/2 rounded-full bg-orange-200/35 blur-3xl"></span>
        </div>

        <div class="relative mx-auto grid min-h-[calc(100vh-8rem)] max-w-7xl items-center gap-8 px-3 py-10 sm:px-4 lg:grid-cols-[1.05fr_0.95fr] lg:py-16">
            <div class="mx-auto max-w-2xl text-center lg:mx-0 lg:text-left">
                <p class="inline-flex rounded-full bg-white px-4 py-2 text-sm font-black uppercase tracking-[0.2em] text-brand-600 shadow-soft">
                    Грешка 404
                </p>

                <h1 class="mt-5 text-4xl font-black tracking-tight text-stone-950 sm:text-6xl">
                    Упс, пицата зави в грешната улица.
                </h1>

                <p class="mt-4 text-base font-medium leading-7 text-stone-600 sm:text-lg">
                    Страницата я няма, но добрите неща са още в менюто. След
                    <span id="not-found-countdown" class="font-black text-brand-600">{{ $redirectAfterSeconds }}</span>
                    секунди ще те върнем на началото.
                </p>

                <div class="mt-7 flex flex-col items-center gap-3 sm:flex-row lg:justify-start">
                    <a href="{{ route('home') }}"
                       class="inline-flex w-full items-center justify-center rounded-full bg-brand-500 px-6 py-3 text-base font-black text-white shadow-soft transition hover:-translate-y-0.5 hover:bg-brand-600 sm:w-auto">
                        Към началото
                    </a>
                    <a href="{{ route('menu') }}"
                       class="inline-flex w-full items-center justify-center rounded-full bg-white px-6 py-3 text-base font-black text-stone-800 shadow-soft transition hover:-translate-y-0.5 hover:text-brand-600 sm:w-auto">
                        Виж менюто
                    </a>
                </div>

                <div class="mt-8 overflow-hidden rounded-full bg-white shadow-inner ring-1 ring-stone-200">
                    <div class="h-3 rounded-full bg-gradient-to-r from-brand-500 via-gold-500 to-brand-500 motion-safe:animate-[not-found-timer_10s_linear_forwards]"></div>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-xl">
                <div class="absolute inset-x-8 bottom-6 h-12 rounded-full bg-stone-900/15 blur-2xl"></div>

                <div class="relative overflow-hidden rounded-[2rem] bg-white p-4 shadow-card ring-1 ring-stone-200 sm:p-7">
                    <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-gold-400/30"></div>
                    <div class="absolute -bottom-20 -left-20 h-48 w-48 rounded-full bg-brand-200/40"></div>
                    <div class="absolute left-8 top-8 h-16 w-16 rounded-full bg-orange-200/40 blur-xl"></div>

                    <svg class="not-found-art relative z-10 h-auto w-full" viewBox="0 0 560 440" role="img" aria-labelledby="pizza-404-title pizza-404-desc">
                        <title id="pizza-404-title">Паднало парче пица</title>
                        <desc id="pizza-404-desc">Детайлна илюстрация на пица, която е паднала от отворена кутия, с разтеглен кашкавал и надпис 404.</desc>

                        <defs>
                            <linearGradient id="pizzaCrust" x1="0%" x2="100%" y1="0%" y2="100%">
                                <stop offset="0%" stop-color="#F97316"/>
                                <stop offset="100%" stop-color="#BD8F08"/>
                            </linearGradient>
                            <linearGradient id="pizzaCheese" x1="0%" x2="100%" y1="0%" y2="100%">
                                <stop offset="0%" stop-color="#FFE066"/>
                                <stop offset="58%" stop-color="#FFCB08"/>
                                <stop offset="100%" stop-color="#F6B909"/>
                            </linearGradient>
                            <filter id="softShadow" x="-20%" y="-20%" width="140%" height="140%">
                                <feDropShadow dx="0" dy="14" stdDeviation="10" flood-color="#1C1917" flood-opacity=".18"/>
                            </filter>
                        </defs>

                        <path d="M64 348c72 39 356 39 432 0 24-12 21-31-6-42-82-35-328-34-413 1-27 11-31 29-13 41z" fill="#E7E5E4"/>
                        <path d="M118 367c54 14 260 15 322 1" fill="none" stroke="#D6D3D1" stroke-width="8" stroke-linecap="round"/>

                        <g class="motion-safe:animate-[not-found-steam_3.2s_ease-in-out_infinite]" opacity=".65">
                            <path d="M106 116c21-26 48-25 69 0M395 121c22-22 45-22 67 0" fill="none" stroke="#D6D3D1" stroke-width="8" stroke-linecap="round"/>
                            <path d="M464 173c14-16 29-16 43 0" fill="none" stroke="#E7E5E4" stroke-width="7" stroke-linecap="round"/>
                        </g>

                        <g class="motion-safe:animate-[not-found-box_4s_ease-in-out_infinite]" filter="url(#softShadow)">
                            <path d="M108 162h277l71 104H176z" fill="#FFF7ED" stroke="#D6D3D1" stroke-width="7" stroke-linejoin="round"/>
                            <path d="M135 187h210l33 49H175z" fill="#EB1C22" opacity=".12"/>
                            <path d="M176 266h280l-49 86H121z" fill="#FAFAF9" stroke="#D6D3D1" stroke-width="7" stroke-linejoin="round"/>
                            <path d="M156 288h249l-20 37H137z" fill="#F5F5F4"/>
                            <path d="M205 296c24 18 77 22 111 7" fill="none" stroke="#A8A29E" stroke-width="7" stroke-linecap="round"/>
                            <circle cx="230" cy="276" r="7" fill="#78716C"/>
                            <circle cx="295" cy="281" r="7" fill="#78716C"/>
                            <path d="M169 173c41-14 128-13 169 1" fill="none" stroke="#FFCB08" stroke-width="11" stroke-linecap="round"/>
                            <text x="255" y="224" fill="#90191C" font-family="Inter, Arial, sans-serif" font-size="62" font-weight="900" text-anchor="middle">404</text>
                        </g>

                        <g class="motion-safe:animate-[not-found-cheese_2.8s_ease-in-out_infinite]">
                            <path d="M332 286c24 16 48 14 72-5" fill="none" stroke="#FFCB08" stroke-width="11" stroke-linecap="round"/>
                            <path d="M352 304c17 11 34 10 51-3" fill="none" stroke="#FFE066" stroke-width="8" stroke-linecap="round"/>
                            <path d="M147 319c-20 10-38 8-55-7" fill="none" stroke="#FFCB08" stroke-width="9" stroke-linecap="round"/>
                        </g>

                        <g class="motion-safe:animate-[not-found-slice_3.4s_ease-in-out_infinite]" filter="url(#softShadow)">
                            <path d="M247 80l158 252-300-47z" fill="url(#pizzaCheese)" stroke="#BD8F08" stroke-width="8" stroke-linejoin="round"/>
                            <path d="M247 80c40 17 70 49 91 94" fill="none" stroke="url(#pizzaCrust)" stroke-width="22" stroke-linecap="round"/>
                            <path d="M143 291c35 30 84 37 141 22" fill="none" stroke="#F6B909" stroke-width="12" stroke-linecap="round"/>
                            <path d="M227 166c24 4 45 22 53 49" fill="none" stroke="#FFE066" stroke-width="11" stroke-linecap="round"/>
                            <circle cx="233" cy="222" r="20" fill="#EB1C22"/>
                            <circle cx="292" cy="262" r="17" fill="#C5171C"/>
                            <circle cx="181" cy="266" r="16" fill="#EB1C22"/>
                            <circle cx="255" cy="303" r="12" fill="#90191C"/>
                            <path d="M202 246c11 8 23 9 36 2" fill="none" stroke="#7A1417" stroke-width="5" stroke-linecap="round" opacity=".35"/>
                            <path d="M316 188l23 6M174 226l-23-9M307 306l22 14" stroke="#14532D" stroke-width="8" stroke-linecap="round"/>
                        </g>

                        <g class="motion-safe:animate-[not-found-toppings_2.4s_ease-in-out_infinite]">
                            <circle cx="78" cy="284" r="8" fill="#EB1C22"/>
                            <circle cx="430" cy="294" r="7" fill="#EB1C22"/>
                            <circle cx="457" cy="321" r="5" fill="#14532D"/>
                            <path d="M95 326l22 9M419 344l24-8" stroke="#14532D" stroke-width="7" stroke-linecap="round"/>
                        </g>

                        <g fill="#A8A29E">
                            <circle cx="77" cy="257" r="5"/>
                            <circle cx="473" cy="266" r="7"/>
                            <circle cx="489" cy="294" r="4"/>
                            <circle cx="116" cy="356" r="5"/>
                            <circle cx="453" cy="367" r="4"/>
                        </g>

                        <g class="motion-safe:animate-[not-found-spark_2s_ease-in-out_infinite]" fill="#FFCB08">
                            <path d="M94 145l8 17 17 8-17 8-8 17-8-17-17-8 17-8z"/>
                            <path d="M450 82l6 13 13 6-13 6-6 13-6-13-13-6 13-6z"/>
                        </g>
                    </svg>

                    <div class="relative z-10 mt-4 rounded-2xl bg-stone-50 p-4 text-center">
                        <p class="text-sm font-extrabold text-stone-900">Петсекундно правило?</p>
                        <p class="mt-1 text-sm text-stone-500">Не рискуваме. По-добре избери нова, гореща пица.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <style>
        .not-found-art g {
            transform-box: fill-box;
            transform-origin: center;
        }

        @keyframes not-found-timer {
            from {
                width: 100%;
            }

            to {
                width: 0%;
            }
        }

        @keyframes not-found-slice {
            0%, 100% {
                transform: translateY(0) rotate(-4deg);
            }

            50% {
                transform: translateY(-12px) rotate(3deg);
            }
        }

        @keyframes not-found-box {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(5px);
            }
        }

        @keyframes not-found-cheese {
            0%, 100% {
                opacity: 0.45;
                transform: translateY(0);
            }

            50% {
                opacity: 1;
                transform: translateY(4px);
            }
        }

        @keyframes not-found-steam {
            0%, 100% {
                opacity: 0.35;
                transform: translateY(5px);
            }

            50% {
                opacity: 0.75;
                transform: translateY(-7px);
            }
        }

        @keyframes not-found-toppings {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-6px) rotate(8deg);
            }
        }

        @keyframes not-found-spark {
            0%, 100% {
                opacity: 0.45;
                transform: scale(0.9);
            }

            50% {
                opacity: 1;
                transform: scale(1.08);
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const countdown = document.getElementById('not-found-countdown');
            const homeUrl = @json(route('home'));
            let secondsLeft = {{ $redirectAfterSeconds }};

            const intervalId = window.setInterval(() => {
                secondsLeft -= 1;

                if (countdown) {
                    countdown.textContent = secondsLeft.toString();
                }

                if (secondsLeft <= 0) {
                    window.clearInterval(intervalId);
                    window.location.assign(homeUrl);
                }
            }, 1000);
        });
    </script>
@endpush
