<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-[var(--tp-bark)] px-5 py-3 text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-paper-soft)] transition hover:bg-[var(--tp-bark-strong)] focus:bg-[var(--tp-bark-strong)] active:bg-[rgba(31,52,64,1)] focus:outline-none focus:ring-2 focus:ring-[rgba(108,135,148,0.45)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]']) }}>
    {{ $slot }}
</button>
