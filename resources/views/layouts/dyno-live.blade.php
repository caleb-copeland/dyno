<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0A0A0B">
    <title>{{ $title ?? 'Dyno' }}</title>
    @include('partials.pwa-head')
    @include('partials.dyno-styles')
    @livewireStyles
</head>
<body>
    <main class="dyno-wrap">
        {{ $slot }}
    </main>
    @include('partials.dyno-tabs')
    @livewireScripts
</body>
</html>
