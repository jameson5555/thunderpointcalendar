@props([
    'id',
    'label' => 'Stay Dates',
    'startName' => 'start_date',
    'endName' => 'end_date',
    'startValue' => '',
    'endValue' => '',
    'placeholder' => 'Choose arrival and departure',
    'emptySummary' => 'Choose arrival and departure',
    'disabledRangesByArea' => [],
    'areaInputName' => 'living_area_ids[]',
    'required' => false,
])

@php
    $summaryId = $id.'__summary';
@endphp

<div
    x-data="dateRangePicker({
        startValue: @js($startValue),
        endValue: @js($endValue),
        emptySummary: @js($emptySummary),
        disabledRangesByArea: @js($disabledRangesByArea),
        areaInputName: @js($areaInputName),
    })"
    x-init="init()"
    class="space-y-2"
    data-date-range-picker
    data-persistent-range-picker
>
    <x-input-label :for="$id" :value="$label" />

    <div class="relative">
        <input
            x-ref="display"
            id="{{ $id }}"
            type="text"
            class="tp-date-range-input mt-2 w-full"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            aria-describedby="{{ $summaryId }}"
            @required($required)
            readonly
        >

        <button
            x-ref="trigger"
            type="button"
            class="tp-date-range-trigger absolute right-3 top-[0.9rem]"
            @click="toggle()"
            aria-label="Open date range picker"
            :aria-expanded="isOpen.toString()"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8 2V5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                <path d="M16 2V5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                <path d="M3.5 9.5H20.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                <path d="M6 4.5H18C19.6569 4.5 21 5.84315 21 7.5V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V7.5C3 5.84315 4.34315 4.5 6 4.5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
            </svg>
        </button>
    </div>

    <div class="flex items-center justify-between gap-3">
        <p id="{{ $summaryId }}" class="text-xs font-semibold uppercase tracking-[0.12em] text-[var(--tp-muted)]" x-text="summary"></p>

        <button
            type="button"
            class="tp-button-ghost px-3 py-1.5 text-xs uppercase tracking-[0.12em]"
            x-cloak
            x-show="hasSelection"
            @click="clear()"
        >
            Clear
        </button>
    </div>

    <input x-ref="start" type="hidden" name="{{ $startName }}" value="{{ $startValue }}">
    <input x-ref="end" type="hidden" name="{{ $endName }}" value="{{ $endValue }}">
</div>
