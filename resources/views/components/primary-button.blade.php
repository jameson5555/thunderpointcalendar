<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-full bg-[var(--tp-ember)] px-5 py-3 text-sm font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-[var(--tp-pine)] focus:bg-[var(--tp-pine)] active:bg-[var(--tp-bark)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-lake)] focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
