@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-[1rem] border border-[rgba(88,109,91,0.18)] bg-[rgba(88,109,91,0.08)] px-4 py-3 text-sm font-semibold text-[var(--tp-pine)]']) }}>
        {{ $status }}
    </div>
@endif
