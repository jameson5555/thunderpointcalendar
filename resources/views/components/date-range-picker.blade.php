@props([
    'id',
    'label' => 'Stay Dates',
    'startName' => 'start_date',
    'endName' => 'end_date',
    'startValue' => '',
    'endValue' => '',
    'disabledRangesByArea' => [],
    'areaInputName' => 'living_area_ids[]',
    'required' => false,
    'invalid' => false,
    'errorId' => null,
])

@php
    $hintId = $id.'__hint';
    $pickerId = $id.'__picker';
    $pickerTitleId = $id.'__picker_title';
    $clientErrorId = $id.'__client_error';
    $describedBy = collect([$hintId, $clientErrorId, $errorId])->filter()->implode(' ');
@endphp

<fieldset
    x-data="dateRangePicker({
        startValue: @js($startValue),
        endValue: @js($endValue),
        disabledRangesByArea: @js($disabledRangesByArea),
        areaInputName: @js($areaInputName),
    })"
    x-init="init()"
    @calendar-booking-dates.window="setDates($event.detail.startDate, $event.detail.endDate, $event.detail.disabledRangesByArea)"
    class="space-y-2"
    data-date-range-picker
>
    <legend class="sr-only">{{ $label }}</legend>

    <div x-ref="positioningRoot" class="relative space-y-2">
    <div class="tp-date-fields">
        <div>
            <x-input-label :for="$id.'_start_display'" value="Arrive" />
            <div class="tp-date-control mt-1">
                <input
                    x-ref="startDisplay"
                    id="{{ $id }}_start_display"
                    type="text"
                    class="tp-date-range-input"
                    placeholder="MM/DD/YYYY"
                    aria-label="Arrival date"
                    autocomplete="off"
                    inputmode="numeric"
                    aria-describedby="{{ $describedBy }}"
                    @change="parseField('start')"
                    @keydown.enter.prevent="commitTypedField('start')"
                    @keydown.arrow-down.prevent="open('start')"
                    @if ($invalid) aria-invalid="true" @endif
                    @required($required)
                >

                <button
                    x-ref="startTrigger"
                    type="button"
                    class="tp-date-range-trigger"
                    @click="toggle('start')"
                    aria-label="Choose arrival date from calendar"
                    aria-controls="{{ $pickerId }}"
                    :aria-expanded="(isOpen && activeField === 'start').toString()"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 2V5M16 2V5M3.5 9.5H20.5M6 4.5H18C19.6569 4.5 21 5.84315 21 7.5V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V7.5C3 5.84315 4.34315 4.5 6 4.5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <x-input-label :for="$id.'_end_display'" value="Depart" />
            <div class="tp-date-control mt-1">
                <input
                    x-ref="endDisplay"
                    id="{{ $id }}_end_display"
                    type="text"
                    class="tp-date-range-input"
                    placeholder="MM/DD/YYYY"
                    aria-label="Departure date"
                    autocomplete="off"
                    inputmode="numeric"
                    aria-describedby="{{ $describedBy }}"
                    @change="parseField('end')"
                    @keydown.enter.prevent="commitTypedField('end')"
                    @keydown.arrow-down.prevent="open('end')"
                    @if ($invalid) aria-invalid="true" @endif
                    @required($required)
                >

                <button
                    x-ref="endTrigger"
                    type="button"
                    class="tp-date-range-trigger"
                    @click="toggle('end')"
                    aria-label="Choose departure date from calendar"
                    aria-controls="{{ $pickerId }}"
                    :aria-expanded="(isOpen && activeField === 'end').toString()"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M8 2V5M16 2V5M3.5 9.5H20.5M6 4.5H18C19.6569 4.5 21 5.84315 21 7.5V18C21 19.6569 19.6569 21 18 21H6C4.34315 21 3 19.6569 3 18V7.5C3 5.84315 4.34315 4.5 6 4.5Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <p id="{{ $hintId }}" class="sr-only">Enter each date as MM/DD/YYYY, or use its calendar button.</p>

    <div
        x-cloak
        x-show="isOpen"
        x-ref="picker"
        id="{{ $pickerId }}"
        class="tp-date-picker-popover"
        :style="pickerStyle"
        role="dialog"
        aria-labelledby="{{ $pickerTitleId }}"
        @click.outside="close()"
        @keydown.tab="trapPickerFocus($event)"
    >
        <h3 id="{{ $pickerTitleId }}" class="sr-only" x-text="pickerTitle"></h3>
        <div class="tp-vanilla-calendar">
            <div x-ref="calendar"></div>
        </div>
        <div class="tp-date-picker-actions">
            <button type="button" class="tp-button-ghost min-h-11 px-3 py-2 text-xs" @click="close()">Close</button>
        </div>
    </div>

    <p id="{{ $clientErrorId }}" class="text-sm text-[var(--tp-error)]" role="alert" x-show="invalidRangeMessage !== ''" x-text="invalidRangeMessage"></p>

    <input x-ref="start" type="hidden" name="{{ $startName }}" value="{{ $startValue }}">
    <input x-ref="end" type="hidden" name="{{ $endName }}" value="{{ $endValue }}">
    </div>
</fieldset>
