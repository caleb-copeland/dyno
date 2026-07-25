<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-[#F2F2F3] px-5 py-3 text-sm font-semibold text-[#0A0A0B] hover:bg-white focus:outline-none focus:ring-2 focus:ring-[#3B82F6] focus:ring-offset-2 focus:ring-offset-[#0A0A0B] active:scale-[.98] transition']) }}>
    {{ $slot }}
</button>
