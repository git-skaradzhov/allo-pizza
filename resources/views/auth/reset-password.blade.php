@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Нова парола');
@endphp

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-center text-3xl font-bold">Нова парола</h1>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4 rounded-2xl border border-stone-200 bg-white p-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium">Имейл</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-brand-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Нова парола</label>
                <input id="password" name="password" type="password" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('password')
                    <p class="mt-1 text-sm text-brand-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium">Потвърди паролата</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-medium text-white hover:bg-brand-600">
                Смени паролата
            </button>
        </form>
    </div>
@endsection

