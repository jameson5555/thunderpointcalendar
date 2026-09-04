@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-surface-raised)] px-4 py-3 text-start text-base font-semibold text-[var(--tp-bark)] shadow-sm transition duration-150 ease-in-out'
            : 'block w-full rounded-[1rem] px-4 py-3 text-start text-base font-semibold text-[var(--tp-muted)] transition duration-150 ease-in-out hover:bg-[var(--tp-surface-raised)] hover:text-[var(--tp-bark)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
