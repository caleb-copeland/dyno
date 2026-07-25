<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-[#EF4444] px-5 py-3 text-sm font-semibold text-white hover:bg-[#f05656] active:bg-[#d63b3b] focus:outline-none focus:ring-2 focus:ring-[#EF4444] focus:ring-offset-2 focus:ring-offset-[#0A0A0B] active:scale-[.98] transition']) }}>
    {{ $slot }}
</button>
