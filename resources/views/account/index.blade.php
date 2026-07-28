@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Профил');
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Профил'],
    ]" />

    <div class="mb-8 flex flex-col gap-4 rounded-3xl bg-brand-500 p-6 text-white shadow-soft sm:p-8 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/75">Клиентски дашборд</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight sm:text-4xl">Здравейте, {{ auth()->user()->name }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-white/80">
                Управлявайте профила си, адресите за доставка, паролата и всички поръчки от едно място.
            </p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="rounded-2xl bg-white px-5 py-3 text-sm font-bold text-brand-600 transition hover:bg-gold-100">
                Изход от профила
            </button>
        </form>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('account.orders') }}" class="rounded-3xl border border-stone-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-soft">
            <p class="text-sm font-semibold text-stone-500">Всички поръчки</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $ordersCount }}</p>
            <p class="mt-1 text-sm text-brand-600">Преглед на историята</p>
        </a>

        <a href="{{ route('account.addresses') }}" class="rounded-3xl border border-stone-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-soft">
            <p class="text-sm font-semibold text-stone-500">Запазени адреси</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $addressesCount }}</p>
            <p class="mt-1 text-sm text-brand-600">Добавяне и редакция</p>
        </a>

        <div class="rounded-3xl border border-stone-200 bg-white p-5">
            <p class="text-sm font-semibold text-stone-500">Последна поръчка</p>
            @if ($lastOrder)
                <p class="mt-2 text-xl font-extrabold">{{ $lastOrder->order_number }}</p>
                <p class="mt-1 text-sm text-stone-600">{{ $lastOrder->status->label() }} · {{ money($lastOrder->total) }}</p>
            @else
                <p class="mt-2 text-xl font-extrabold">Няма още</p>
                <p class="mt-1 text-sm text-stone-600">Направете първата си поръчка.</p>
            @endif
        </div>

        <div class="rounded-3xl border border-stone-200 bg-white p-5">
            <p class="text-sm font-semibold text-stone-500">Имейл статус</p>
            <p class="mt-2 text-xl font-extrabold">{{ auth()->user()->email_verified_at ? 'Потвърден' : 'Непотвърден' }}</p>
            <p class="mt-1 text-sm text-stone-600">{{ auth()->user()->email }}</p>
        </div>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-8 lg:col-span-2">
            <div class="rounded-3xl border border-stone-200 bg-white p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold">Лични данни</h2>
                    <p class="mt-1 text-sm text-stone-600">Тези данни се използват при поръчка и доставка.</p>
                </div>

                <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-semibold">Име</label>
                            <input id="name" name="name" type="text" value="{{ old('name', auth()->user()->name) }}" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                            @error('name') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-semibold">Телефон</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $customer?->phone) }}" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                            @error('phone') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold">Имейл</label>
                        <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                        @error('email') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-brand-600">
                        Запази промените
                    </button>
                </form>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6">
                <div class="mb-5">
                    <h2 class="text-xl font-bold">Сигурност и парола</h2>
                    <p class="mt-1 text-sm text-stone-600">Сменете паролата си или използвайте линка за възстановяване, ако сте я забравили.</p>
                </div>

                <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="current_password" class="block text-sm font-semibold">Текуща парола</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                        @error('current_password') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="password" class="block text-sm font-semibold">Нова парола</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                            @error('password') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold">Повтори новата парола</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <button type="submit" class="rounded-2xl bg-brand-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-brand-600">
                            Смени паролата
                        </button>
                    </div>
                </form>

                <form method="POST" action="{{ route('account.password.reset-link') }}" class="mt-4 rounded-2xl bg-stone-50 p-4">
                    @csrf
                    <p class="text-sm text-stone-600">
                        Забравили сте текущата парола? Ще изпратим линк за възстановяване на {{ auth()->user()->email }}.
                    </p>
                    <button type="submit" class="mt-3 text-sm font-semibold text-brand-600 hover:underline">
                        Изпрати линк за възстановяване
                    </button>
                </form>
            </div>
        </div>

        <aside class="space-y-8">
            <div class="rounded-3xl border border-stone-200 bg-white p-6">
                <h2 class="text-xl font-bold">Бързи действия</h2>
                <div class="mt-4 space-y-3">
                    <a href="{{ route('menu') }}" class="block rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold transition hover:border-brand-300 hover:bg-brand-50">Нова поръчка</a>
                    <a href="{{ route('account.orders') }}" class="block rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold transition hover:border-brand-300 hover:bg-brand-50">Моите поръчки</a>
                    <a href="{{ route('account.addresses') }}" class="block rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold transition hover:border-brand-300 hover:bg-brand-50">Адреси за доставка</a>
                    @if (auth()->user()->isAdministrator())
                        <a href="{{ url('/admin/user/login') }}" class="block rounded-2xl border border-stone-200 px-4 py-3 text-sm font-semibold transition hover:border-brand-300 hover:bg-brand-50">Админ дашборд</a>
                    @endif
                </div>
            </div>

            <div class="rounded-3xl border border-stone-200 bg-white p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-bold">Последни поръчки</h2>
                    <a href="{{ route('account.orders') }}" class="text-sm font-semibold text-brand-600 hover:underline">Всички</a>
                </div>

                @forelse ($recentOrders as $order)
                    <a href="{{ route('account.orders.show', $order) }}" class="mb-3 block rounded-2xl border border-stone-200 px-4 py-3 transition hover:border-brand-300">
                        <div class="flex justify-between gap-3">
                            <span class="font-semibold">{{ $order->order_number }}</span>
                            <span>{{ money($order->total) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-stone-600">{{ $order->created_at->format('d.m.Y H:i') }} · {{ $order->status->label() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-stone-600">Все още нямате поръчки.</p>
                    <a href="{{ route('menu') }}" class="mt-4 inline-flex rounded-2xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-600">Към менюто</a>
                @endforelse
            </div>
        </aside>
    </div>
@endsection

