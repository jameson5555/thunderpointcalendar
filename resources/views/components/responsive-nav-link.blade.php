@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex min-h-12 w-full items-center rounded-[0.9rem] bg-[rgba(26,140,145,0.09)] px-4 py-3 text-start text-base font-bold text-[var(--tp-bark)] transition duration-150 ease-in-out'
            : 'flex min-h-12 w-full items-center rounded-[0.9rem] px-4 py-3 text-start text-base font-semibold text-[var(--tp-muted)] transition duration-150 ease-in-out hover:bg-[var(--tp-surface-muted)] hover:text-[var(--tp-bark)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    <span>{{ $slot }}</span>
</a>
