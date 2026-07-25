@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[11px] font-semibold uppercase tracking-[0.12em] text-[#8A8A90]']) }}>
    {{ $value ?? $slot }}
</label>
