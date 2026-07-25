@props(['value'])

<label {{ $attributes->merge(['class' => 'label block']) }}>
    {{ $value ?? $slot }}
</label>
