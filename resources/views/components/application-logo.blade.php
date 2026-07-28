{{-- Dyno "Core" mark — four focus-area arcs around an ink centre.
     Ring colours are fixed (App\Enums\FocusArea::accentHex); the centre dot
     uses currentColor so it follows the surrounding text colour. --}}
<svg viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dyno" {{ $attributes }}>
    <g transform="rotate(-90 64 64)" fill="none" stroke-width="10" stroke-linecap="round">
        <circle cx="64" cy="64" r="38" stroke="#F5A524" stroke-dasharray="36 202.761" stroke-dashoffset="0"/>
        <circle cx="64" cy="64" r="38" stroke="#22C55E" stroke-dasharray="36 202.761" stroke-dashoffset="-59.69"/>
        <circle cx="64" cy="64" r="38" stroke="#14B8A6" stroke-dasharray="36 202.761" stroke-dashoffset="-119.38"/>
        <circle cx="64" cy="64" r="38" stroke="#3B82F6" stroke-dasharray="36 202.761" stroke-dashoffset="-179.07"/>
    </g>
    <circle cx="64" cy="64" r="8" fill="currentColor"/>
</svg>
