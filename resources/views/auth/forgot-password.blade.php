@extends('layouts.app')

@php
    use App\Support\Seo\SeoBuilder;

    $seo = app(SeoBuilder::class)->forPrivatePage('Забравена парола');
@endphp

@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="mb-6 text-center text-3xl font-bold">Забравена парола</h1>

        <p class="mb-4 text-center text-sm text-stone-600">
            Въведете имейла си и ще ви изпратим линк за възстановяване на паролата.
        </p>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4 rounded-2xl border border-stone-200 bg-white p-6">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">Имейл</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2">
                @error('email')
                    <p class="mt-1 text-sm text-brand-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-lg bg-brand-500 px-4 py-3 font-medium text-white hover:bg-brand-600">
                Изпрати линк
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-stone-600">
            <a href="{{ route('login') }}" class="text-brand-600 hover:underline">Обратно към вход</a>
        </p>
    </div>
@endsection

