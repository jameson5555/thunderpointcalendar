@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold uppercase tracking-[0.12em] text-[rgba(61,52,39,0.72)]']) }}>
    {{ $value ?? $slot }}
</label>
