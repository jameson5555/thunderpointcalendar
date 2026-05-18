@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-full border border-[rgba(61,52,39,0.14)] bg-white/80 px-4 py-3 text-sm font-semibold text-[var(--tp-bark)] shadow-sm transition duration-150 ease-in-out'
            : 'inline-flex items-center rounded-full border border-transparent px-4 py-3 text-sm font-semibold text-[rgba(61,52,39,0.66)] transition duration-150 ease-in-out hover:border-[rgba(61,52,39,0.14)] hover:bg-white/70 hover:text-[var(--tp-bark)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
