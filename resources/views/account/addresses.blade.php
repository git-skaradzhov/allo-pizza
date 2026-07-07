@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Адреси');
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Начало', 'url' => route('home')],
        ['label' => 'Профил', 'url' => route('account.index')],
        ['label' => 'Адреси'],
    ]" />

    @if (session('status'))
        <div class="mb-6 rounded-2xl bg-green-50 px-4 py-3 text-sm font-medium text-green-800">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-extrabold tracking-tight">Моите адреси</h1>
        <a href="{{ route('account.index') }}" class="text-sm font-semibold text-brand-600 hover:underline">Назад към профила</a>
    </div>

    <div class="grid gap-8 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($addresses as $address)
                <div class="rounded-3xl border border-stone-200 bg-white p-5">
                    <div id="address-view-{{ $address->id }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-bold">{{ $address->label ?? 'Адрес' }}</h2>
                                    @if ($address->is_default)
                                        <span class="rounded-full bg-gold-500/20 px-2 py-0.5 text-xs font-semibold text-brand-700">По подразбиране</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-sm text-stone-600">{{ $address->address_line }}</p>
                                <p class="text-sm text-stone-600">{{ $address->city }} {{ $address->postal_code }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button"
                                        class="rounded-lg p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-brand-600"
                                        title="Редактирай"
                                        onclick="toggleAddressEdit({{ $address->id }}, true)">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-brand-600" title="Изтрий">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <form id="address-edit-{{ $address->id }}" method="POST" action="{{ route('account.addresses.update', $address) }}" class="hidden space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="label-{{ $address->id }}" class="block text-sm font-semibold">Етикет</label>
                            <input id="label-{{ $address->id }}" name="label" type="text" value="{{ old('label', $address->label) }}" placeholder="Вкъщи, Офис..." class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                        </div>
                        <div>
                            <label for="address_line-{{ $address->id }}" class="block text-sm font-semibold">Адрес</label>
                            <input id="address_line-{{ $address->id }}" name="address_line" type="text" value="{{ old('address_line', $address->address_line) }}" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="city-{{ $address->id }}" class="block text-sm font-semibold">Град</label>
                                <input id="city-{{ $address->id }}" name="city" type="text" value="{{ old('city', $address->city) }}" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                            </div>
                            <div>
                                <label for="postal_code-{{ $address->id }}" class="block text-sm font-semibold">Пощ. код</label>
                                <input id="postal_code-{{ $address->id }}" name="postal_code" type="text" value="{{ old('postal_code', $address->postal_code) }}" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_default" value="1" class="rounded text-brand-500 focus:ring-brand-400" {{ $address->is_default ? 'checked' : '' }}>
                            <span>Адрес по подразбиране</span>
                        </label>
                        <div class="flex gap-2">
                            <button type="submit" class="rounded-2xl bg-brand-500 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-600">Запази</button>
                            <button type="button" class="rounded-2xl border border-stone-300 px-4 py-2.5 text-sm font-semibold text-stone-700 hover:bg-stone-50" onclick="toggleAddressEdit({{ $address->id }}, false)">Отказ</button>
                        </div>
                    </form>
                </div>
            @empty
                <p class="text-stone-600">Нямате запазени адреси.</p>
            @endforelse
        </div>

        <div class="h-fit rounded-3xl border border-stone-200 bg-white p-6">
            <h2 class="mb-4 text-lg font-bold">Нов адрес</h2>
            <form method="POST" action="{{ route('account.addresses.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label for="label" class="block text-sm font-semibold">Етикет</label>
                    <input id="label" name="label" type="text" placeholder="Вкъщи, Офис..." class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                </div>
                <div>
                    <label for="address_line" class="block text-sm font-semibold">Адрес</label>
                    <input id="address_line" name="address_line" type="text" required class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                    @error('address_line') <p class="mt-1 text-sm text-brand-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="city" class="block text-sm font-semibold">Град</label>
                        <input id="city" name="city" type="text" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                    </div>
                    <div>
                        <label for="postal_code" class="block text-sm font-semibold">Пощ. код</label>
                        <input id="postal_code" name="postal_code" type="text" class="mt-1 w-full rounded-xl border-stone-300 focus:border-brand-400 focus:ring-brand-400">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1" class="rounded text-brand-500 focus:ring-brand-400">
                    <span>Адрес по подразбиране</span>
                </label>
                <button type="submit" class="w-full rounded-2xl bg-brand-500 px-4 py-3 font-bold text-white hover:bg-brand-600">Запази адреса</button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleAddressEdit(id, showEdit) {
                document.getElementById('address-view-' + id).classList.toggle('hidden', showEdit);
                document.getElementById('address-edit-' + id).classList.toggle('hidden', !showEdit);
            }
        </script>
    @endpush
@endsection
