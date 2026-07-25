<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-accent px-5 py-3 text-sm font-semibold text-canvas hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-canvas active:scale-[.98] transition']) }}>
    {{ $slot }}
</button>
