<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full border border-[rgba(152,97,70,0.24)] bg-[rgba(152,97,70,0.1)] px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--tp-ember)] transition hover:bg-[rgba(152,97,70,0.16)] focus:outline-none focus:ring-2 focus:ring-[rgba(152,97,70,0.24)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]']) }}>
    {{ $slot }}
</button>
