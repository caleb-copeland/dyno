<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0A0A0B">
    <title>@yield('title', 'Dyno')</title>
    @include('partials.dyno-styles')
    <style>
        .tabbar {
            position: fixed; left: 0; right: 0; bottom: 0;
            display: flex; justify-content: space-around;
            background: rgba(20,20,22,.92); backdrop-filter: blur(12px);
            border-top: 1px solid var(--line); padding: 10px 8px calc(10px + env(safe-area-inset-bottom));
        }
        .tab { flex: 1; text-align: center; text-decoration: none; color: var(--text-muted);
               font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em;
               padding: 6px; border-radius: 12px; }
        .tab[aria-current="page"] { color: var(--text); }
        .tab-ico { display: block; font-size: 20px; margin-bottom: 3px; }
    </style>
</head>
<body>
    <main class="dyno-wrap">
        @yield('content')
    </main>

    <nav class="tabbar">
        <a class="tab" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <span class="tab-ico">◎</span>Today
        </a>
        <a class="tab" href="{{ route('history') }}" @if(request()->routeIs('history')) aria-current="page" @endif>
            <span class="tab-ico">≡</span>History
        </a>
        <a class="tab" href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>
            <span class="tab-ico">◔</span>Profile
        </a>
    </nav>
</body>
</html>
