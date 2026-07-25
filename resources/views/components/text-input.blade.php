@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-surface-raised text-ink placeholder-muted px-4 py-3 shadow-none focus:border-accent focus:ring-accent disabled:opacity-60']) }}>
