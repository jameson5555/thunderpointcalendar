@props(['messages' => [], 'errors' => null])

@php
    $summaryMessages = $errors ? $errors->all() : collect($messages)->flatten()->all();
@endphp

@if (count($summaryMessages))
    <section
        {{ $attributes->merge(['class' => 'rounded-[1rem] border-2 border-[var(--tp-error)] bg-[var(--tp-control)] px-4 py-3 text-sm text-[var(--tp-bark)]']) }}
        role="alert"
        tabindex="-1"
        data-error-summary
    >
        <p class="font-bold">Please correct the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach (collect($summaryMessages)->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </section>
@endif
