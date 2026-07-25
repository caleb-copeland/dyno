@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border border-[#26262A] bg-[#1E1E21] text-[#F2F2F3] placeholder-[#8A8A90] px-4 py-3 shadow-none focus:border-[#3B82F6] focus:ring-[#3B82F6] disabled:opacity-60']) }}>
