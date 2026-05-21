<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-[var(--tp-bark)] px-5 py-3 text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-paper-soft)] transition hover:bg-[rgba(75,53,40,0.92)] focus:bg-[rgba(75,53,40,0.92)] active:bg-[rgba(47,37,29,1)] focus:outline-none focus:ring-2 focus:ring-[rgba(108,135,148,0.45)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]']) }}>
    {{ $slot }}
</button>
