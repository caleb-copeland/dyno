@props(['status'])
{{-- Flash status (e.g. "Your password has been reset.") as a toast bubble.
     Auto-hides after 5s; tap to dismiss. Styles live in partials/dyno-styles. --}}
@if ($status)
    <div class="toast" role="status" aria-live="polite"
         x-data="{ show: false }"
         x-init="$nextTick(() => show = true); setTimeout(() => show = false, 5000)"
         x-bind:class="{ 'is-shown': show }"
         x-on:click="show = false">
        <span class="toast__icon"><x-ico name="check" :size="16" /></span>
        <span>{{ $status }}</span>
    </div>
@endif
