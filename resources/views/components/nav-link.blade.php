@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-[var(--tp-border)] bg-[var(--tp-surface-raised)] px-4 py-2.5 text-sm font-semibold text-[var(--tp-bark)] shadow-sm transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full border border-transparent px-4 py-2.5 text-sm font-semibold text-[var(--tp-muted)] transition duration-150 ease-in-out hover:border-[var(--tp-border)] hover:bg-[var(--tp-surface-raised)] hover:text-[var(--tp-bark)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
