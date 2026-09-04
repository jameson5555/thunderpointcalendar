@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border-2 border-[var(--tp-bark)] bg-[var(--tp-surface-raised)] px-4 py-2.5 text-sm font-bold text-[var(--tp-bark)] shadow-sm transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full border-2 border-transparent px-4 py-2.5 text-sm font-semibold text-[var(--tp-muted)] transition duration-150 ease-in-out hover:border-[var(--tp-border)] hover:bg-[var(--tp-surface-raised)] hover:text-[var(--tp-bark)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
