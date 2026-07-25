<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-danger px-5 py-3 text-sm font-semibold text-white hover:opacity-90 active:opacity-80 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2 focus:ring-offset-canvas active:scale-[.98] transition']) }}>
    {{ $slot }}
</button>
