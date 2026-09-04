@props(['disabled' => false, 'invalid' => false, 'errorId' => null])

<input
    @disabled($disabled)
    @if($invalid) aria-invalid="true" @endif
    @if($invalid && $errorId) aria-describedby="{{ $errorId }}" @endif
    {{ $attributes->merge(['class' => 'w-full rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-control)] px-4 py-3 text-[var(--tp-bark)] shadow-sm placeholder:text-[var(--tp-muted)] focus:border-[var(--tp-focus-ring)] focus:ring-[var(--tp-focus)]']) }}
>
