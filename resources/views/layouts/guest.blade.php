<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#0A0A0B">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Dyno') }}</title>

        @include('partials.dyno-styles')

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10"
             style="padding-bottom: calc(40px + env(safe-area-inset-bottom));">
            <a href="/" class="flex items-center gap-3 mb-8 no-underline">
                <x-application-logo class="w-11 h-11 fill-current text-[#F2F2F3]" />
                <span class="text-[#F2F2F3] text-2xl font-extrabold tracking-[-0.03em]">{{ config('app.name', 'Dyno') }}</span>
            </a>

            <div class="w-full sm:max-w-md">
                <div class="bg-[#141416] rounded-[20px] p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
