<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-transparent px-5 py-3 text-sm font-semibold text-ink ring-1 ring-inset ring-line hover:bg-surface-raised focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-canvas disabled:opacity-40 active:scale-[.98] transition']) }}>
    {{ $slot }}
</button>
