@props(['seo'])

@php
    use App\Support\Seo\SeoData;

    $seo = $seo instanceof SeoData ? $seo : null;
@endphp

@if ($seo)
    <title>{{ $seo->title }}</title>

    @if ($seo->description)
        <meta name="description" content="{{ $seo->description }}">
    @endif

    <meta name="robots" content="{{ $seo->robots }}">
    <link rel="canonical" href="{{ $seo->canonical }}">

    <meta property="og:title" content="{{ $seo->ogTitle }}">
    <meta property="og:type" content="{{ $seo->ogType }}">
    <meta property="og:url" content="{{ $seo->ogUrl }}">
    <meta property="og:site_name" content="{{ $storeSetting->store_name ?? config('app.name') }}">
    <meta property="og:locale" content="{{ str_replace('_', '-', config('seo.locale', 'bg_BG')) }}">

    @if ($seo->ogDescription)
        <meta property="og:description" content="{{ $seo->ogDescription }}">
    @endif

    @if ($seo->ogImage)
        <meta property="og:image" content="{{ $seo->ogImage }}">
    @endif

    <meta name="twitter:card" content="{{ $seo->twitterCard }}">
    <meta name="twitter:title" content="{{ $seo->twitterTitle }}">

    @if ($seo->twitterDescription)
        <meta name="twitter:description" content="{{ $seo->twitterDescription }}">
    @endif

    @if ($seo->twitterImage)
        <meta name="twitter:image" content="{{ $seo->twitterImage }}">
    @endif

    @if ($seo->focusKeyword)
        <meta name="keywords" content="{{ $seo->focusKeyword }}">
    @endif
@endif
