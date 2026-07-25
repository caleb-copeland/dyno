@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-[#22C55E]']) }}>
        {{ $status }}
    </div>
@endif
