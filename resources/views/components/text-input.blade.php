@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.94)] px-4 py-3 text-[var(--tp-bark)] shadow-sm placeholder:text-[var(--tp-muted)] focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]']) }}>
