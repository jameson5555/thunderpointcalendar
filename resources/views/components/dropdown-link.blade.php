@props(['active' => false])

@php
$classes = $active
    ? 'block w-full rounded-[1rem] bg-[var(--tp-surface-muted)] px-4 py-3 text-start text-sm font-bold leading-5 text-[var(--tp-bark)]'
    : 'block w-full rounded-[1rem] px-4 py-3 text-start text-sm font-semibold leading-5 text-[var(--tp-bark)] transition duration-150 ease-in-out hover:bg-[rgba(167,130,61,0.08)] focus:bg-[rgba(167,130,61,0.08)]';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active) aria-current="page" @endif>{{ $slot }}</a>
