@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-[1rem] border border-[rgba(26,140,145,0.26)] bg-[rgba(255,252,245,0.92)] px-4 py-3 text-sm font-semibold text-[var(--tp-pine)]']) }}>
        {{ $status }}
    </div>
@endif
