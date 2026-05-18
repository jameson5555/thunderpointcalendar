@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-2xl border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]']) }}>
