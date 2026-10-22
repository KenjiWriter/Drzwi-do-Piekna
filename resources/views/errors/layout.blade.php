<!DOCTYPE html>
{{-- Ekrany błędów są samowystarczalne: bez Vite, bez JS i bez zewnętrznych czcionek, bo mają działać także wtedy, gdy reszta aplikacji nie działa. --}}
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#FAF6F1">
    @yield('head')
    <title>@yield('title') | {{ config('clinic.name', 'Drzwi do Piękna') }}</title>
    <style>
        :root { --bg: #FAF6F1; --ink: #3A2E31; --brand: #5C1F2E; --muted: #6B5F62; --line: #E6DDD3; }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body { margin: 0; min-height: 100vh; min-height: 100svh; display: grid; place-items: center; padding: 24px 16px; background: var(--bg); color: var(--ink); font: 400 1rem/1.7 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { width: 100%; max-width: 32rem; }
        .brand { margin: 0 0 3rem; font: 500 1.5rem/1.2 Georgia, "Times New Roman", serif; color: var(--brand); }
        .brand small { display: block; margin-top: .35rem; font: 400 .6875rem/1 system-ui, sans-serif; letter-spacing: .24em; text-transform: uppercase; color: var(--muted); }
        .code { margin: 0 0 .5rem; font-size: .8125rem; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: 0 0 1rem; font: 500 2.25rem/1.15 Georgia, "Times New Roman", serif; color: var(--brand); }
        p { margin: 0 0 1.5rem; }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0 1.5rem; border: 1px solid var(--brand); font-size: .8125rem; letter-spacing: .14em; text-transform: uppercase; text-decoration: none; color: var(--brand); }
        .btn--fill { background: var(--brand); color: #fff; }
        .btn:focus-visible { outline: 2px solid var(--brand); outline-offset: 3px; }
    </style>
</head>
<body>
    <main>
        <p class="brand">{{ config('clinic.name', 'Drzwi do Piękna') }}<small>{{ config('clinic.tagline', 'Medycyna estetyczna') }}</small></p>
        <p class="code">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a class="btn btn--fill" href="/">Strona główna</a>
            <a class="btn" href="tel:{{ config('clinic.phone_e164', '+48797745224') }}">{{ config('clinic.phone', '797 745 224') }}</a>
        </div>
    </main>
</body>
</html>
