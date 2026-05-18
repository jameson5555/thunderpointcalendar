@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl bg-white/80 px-4 py-3 text-start text-base font-semibold text-[var(--tp-bark)] shadow-sm transition duration-150 ease-in-out'
            : 'block w-full rounded-2xl px-4 py-3 text-start text-base font-semibold text-[rgba(61,52,39,0.72)] transition duration-150 ease-in-out hover:bg-white/70 hover:text-[var(--tp-bark)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
