@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-[0.2em] text-[var(--tp-muted)]']) }}>
    {{ $value ?? $slot }}
</label>
