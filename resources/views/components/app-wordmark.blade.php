@php $u = uniqid('dwm'); @endphp
{{-- Dyno wordmark — "DYNO" set in Helvetica Neue Bold Italic, outlined to
     paths (device-independent), split by a level cut with a 2px channel and
     the Core mark as the O. Letters + O centre use currentColor. --}}
<svg viewBox="0 0 232 96" fill="currentColor" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dyno" {{ $attributes }}>
    <defs>
        <clipPath id="top-{{ $u }}"><rect x="-30" y="18" width="200" height="26.5"/></clipPath>
        <clipPath id="bot-{{ $u }}"><rect x="-30" y="46.5" width="200" height="26.5"/></clipPath>
        <g id="dyn-{{ $u }}">
            <path transform="translate(4 70) scale(0.066 -0.066)" d="M181 132 277 582H395Q437 582 466.5 569.5Q496 557 515.0 534.0Q534 511 542.5 479.5Q551 448 551 409Q551 352 534.5 302.0Q518 252 486.5 214.0Q455 176 408.5 154.0Q362 132 302 132ZM147 714 -3 0H295Q398 0 475.5 33.0Q553 66 604.5 124.5Q656 183 682.0 263.0Q708 343 708 436Q708 569 632.0 641.5Q556 714 405 714Z"/>
            <path transform="translate(49.906 70) scale(0.066 -0.066)" d="M232 277 175 0H333L388 270L736 714H551L329 410L222 714H61Z"/>
            <path transform="translate(89.674 70) scale(0.066 -0.066)" d="M147 714 -3 0H143L245 501H247L443 0H600L751 714H607L505 206H503L309 714Z"/>
        </g>
    </defs>
    <use href="#dyn-{{ $u }}" clip-path="url(#top-{{ $u }})" transform="translate(1.5,0)"/>
    <use href="#dyn-{{ $u }}" clip-path="url(#bot-{{ $u }})" transform="translate(-1.5,0)"/>
    <g transform="translate(184 48)">
        <g transform="rotate(-90)" fill="none" stroke-width="6.4" stroke-linecap="round">
            <circle r="24" stroke="#F5A524" stroke-dasharray="22 128.796" stroke-dashoffset="0"/>
            <circle r="24" stroke="#22C55E" stroke-dasharray="22 128.796" stroke-dashoffset="-37.699"/>
            <circle r="24" stroke="#14B8A6" stroke-dasharray="22 128.796" stroke-dashoffset="-75.398"/>
            <circle r="24" stroke="#3B82F6" stroke-dasharray="22 128.796" stroke-dashoffset="-113.097"/>
        </g>
        <circle r="5.2" fill="currentColor"/>
    </g>
</svg>
