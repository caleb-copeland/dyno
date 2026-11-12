@props(['name', 'size' => 24, 'label' => null])
{{-- Line-icon set — stroke-based, currentColor, WHOOP-style thin geometry.
     Inlined SVG (no icon font / CDN) so it stays CSP- and offline-PWA-safe.
     Decorative by default; pass :label to expose an accessible name. --}}
@php
    $paths = match ($name) {
        // ---- tab bar ----
        'home'     => '<path d="M3 11 12 4l9 7"/><path d="M5 9.5V20h5v-6h4v6h5V9.5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3.5v3M16 3.5v3"/>',
        'activity' => '<path d="M3 12h4l2.5-6 4 12 2.5-6H21"/>',
        'user'     => '<circle cx="12" cy="8.5" r="3.75"/><path d="M5 19.5a7 7 0 0 1 14 0"/>',
        // ---- content states ----
        'mountain' => '<path d="M2.5 20 10 6.5l3.5 6.4L16 9l5.5 11Z"/><path d="m8 12 2 2 1.5-1.8"/>',
        'moon'     => '<path d="M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z"/>',
        'lock'     => '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        default    => '',
    };
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}"
     fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
     @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
     {{ $attributes }}>
    {!! $paths !!}
</svg>
