<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center rounded-2xl bg-transparent px-5 py-3 text-sm font-semibold text-[#F2F2F3] shadow-[inset_0_0_0_1px_#26262A] hover:bg-[#1E1E21] focus:outline-none focus:ring-2 focus:ring-[#3B82F6] focus:ring-offset-2 focus:ring-offset-[#0A0A0B] disabled:opacity-40 active:scale-[.98] transition']) }}>
    {{ $slot }}
</button>
