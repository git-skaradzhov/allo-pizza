@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Потвърждение на имейл');
@endphp

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-4 text-center text-3xl font-extrabold tracking-tight">Потвърдете имейла си</h1>

        <div class="rounded-3xl border border-stone-200 bg-white p-6 text-sm text-stone-600">
            <p>Благодарим за регистрацията! Изпратихме линк за потвърждение на имейла ви. Моля, кликнете върху него, за да активирате акаунта.</p>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-4 rounded-xl bg-green-50 px-3 py-2 font-medium text-green-800">
                    Изпратихме нов линк за потвърждение.
                </p>
            @endif

            <div class="mt-6 flex items-center justify-between">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="rounded-2xl bg-brand-500 px-5 py-2.5 font-bold text-white hover:bg-brand-600">
                        Изпрати отново
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-stone-500 hover:text-brand-600">Изход</button>
                </form>
            </div>
        </div>
    </div>
@endsection
