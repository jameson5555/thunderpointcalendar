@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.9)] px-4 py-3 text-[var(--tp-bark)] shadow-sm placeholder:text-[var(--tp-muted)] focus:border-[var(--tp-bark)] focus:ring-[rgba(108,135,148,0.35)]']) }}>
