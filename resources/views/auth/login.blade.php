@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Вход');
@endphp

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-center text-3xl font-bold">Вход</h1>

        <form method="POST" action="{{ route('login') }}" class="space-y-4 rounded-2xl border border-stone-200 bg-white p-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">Имейл</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-brand-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Парола</label>
                <input id="password" name="password" type="password" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember">
                <span>Запомни ме</span>
            </label>

            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-medium text-white hover:bg-brand-600">
                Вход
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-stone-600">
            <a href="{{ route('password.request') }}" class="text-brand-600 hover:underline">Забравена парола?</a>
        </p>
        <p class="mt-2 text-center text-sm text-stone-600">
            Нямате акаунт?
            <a href="{{ route('register') }}" class="text-brand-600 hover:underline">Регистрация</a>
        </p>
    </div>
@endsection

