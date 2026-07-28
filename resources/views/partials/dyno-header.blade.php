{{-- App brand bar — the wordmark, linking home. Sits at the top of the
     content column on member pages (the runner stays chrome-free). --}}
<header class="dyno-header">
    <a href="{{ route('dashboard') }}" class="dyno-brand" aria-label="Dyno — home">
        <x-app-wordmark class="dyno-wordmark" />
    </a>
</header>
