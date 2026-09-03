<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full border border-[var(--tp-error)] bg-[rgba(145,60,25,0.1)] px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--tp-error)] transition hover:bg-[rgba(145,60,25,0.16)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]']) }}>
    {{ $slot }}
</button>
