@props(['status'])

@if ($status)
    <div role="status" {{ $attributes->merge(['class' => 'rounded-[1rem] border border-[var(--tp-status)] bg-[rgba(255,252,245,0.92)] px-4 py-3 text-sm font-semibold text-[var(--tp-status)]']) }}>
        {{ $status }}
    </div>
@endif
