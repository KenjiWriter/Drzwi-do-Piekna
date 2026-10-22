<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FAF6F1">

    @php($seo = $page['props']['seo'] ?? null)
    @if ($seo)
        <title inertia>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}" inertia="description">
        <link rel="canonical" href="{{ $seo['canonical'] }}" inertia="canonical">
        @if ($seo['noindex'] ?? false)
            <meta name="robots" content="noindex, follow">
        @endif
        <meta property="og:type" content="{{ $seo['ogType'] ?? 'website' }}" inertia="og:type">
        <meta property="og:locale" content="{{ $seo['locale'] }}" inertia="og:locale">
        <meta property="og:site_name" content="{{ $seo['siteName'] }}" inertia="og:site_name">
        <meta property="og:title" content="{{ $seo['title'] }}" inertia="og:title">
        <meta property="og:description" content="{{ $seo['description'] }}" inertia="og:description">
        <meta property="og:url" content="{{ $seo['canonical'] }}" inertia="og:url">
        @if ($seo['image'])
            <meta property="og:image" content="{{ $seo['image'] }}" inertia="og:image">
            <meta name="twitter:card" content="summary_large_image" inertia="twitter:card">
        @endif
        @foreach ($seo['preload'] ?? [] as $p)
            <link rel="preload" as="image" href="{{ $p['href'] }}" imagesrcset="{{ $p['srcset'] }}" imagesizes="{{ $p['sizes'] }}" media="{{ $p['media'] }}" fetchpriority="high">
        @endforeach
        @foreach ($seo['jsonLd'] as $schema)
            <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
        @endforeach
    @else
        <title inertia>{{ config('app.name') }}</title>
    @endif

    {{-- Animacja powitalna: gra raz na sesję, nie gra przy ograniczonym ruchu ani w trybie automatyzacji. ?intro wymusza odtworzenie. --}}
    <script>
        (function () {
            var d = document.documentElement;
            try {
                var forced = /[?&]intro(=|&|$)/.test(location.search);
                var seen = sessionStorage.getItem('ddp-intro');
                var calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches || navigator.webdriver;
                if (forced || (!seen && !calm)) { d.setAttribute('data-intro', 'play'); }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="ddp">
    @inertia
</body>
</html>
