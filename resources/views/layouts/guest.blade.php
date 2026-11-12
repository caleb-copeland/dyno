<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#0A0A0B">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dyno') }}</title>

        @include('partials.favicons')
        @include('partials.dyno-styles')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10"
             style="padding-bottom: calc(40px + env(safe-area-inset-bottom));">
            <a href="/" class="mb-8 no-underline text-ink" aria-label="Dyno">
                <x-app-wordmark class="w-auto" style="height:60px;display:block;" />
            </a>

            <div class="w-full sm:max-w-md">
                <div class="bg-surface rounded-[20px] p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
