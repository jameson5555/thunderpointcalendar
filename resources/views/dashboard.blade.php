<x-app-layout title="Calendar">
    <x-slot name="header">
        <ul class="tp-part-legend" aria-label="Part color legend">
            @foreach ($livingAreas as $area)
                @php
                    $legendName = str_ends_with($area['name'], ' Part')
                        ? substr($area['name'], 0, -5)
                        : $area['name'];
                @endphp
                <li
                    class="tp-part-legend-item"
                    style="--tp-legend-color: {{ $area['deep_color'] }};"
                >
                    <span aria-hidden="true" class="tp-part-legend-marker shrink-0 rounded-full"></span>
                    <span>{{ $legendName }}</span>
                </li>
            @endforeach
        </ul>
    </x-slot>

    <div class="mx-auto max-w-7xl px-3 py-2 sm:px-6 sm:py-6 lg:px-8">
        @php
            $monthDate = \Carbon\CarbonImmutable::createFromFormat('F Y', $monthLabel, config('app.timezone'));
            $previousMonth = $monthDate->subMonth()->format('Y-m');
            $nextMonth = $monthDate->addMonth()->format('Y-m');
            $livingAreaColorMap = $livingAreas->mapWithKeys(fn ($area) => [$area->name => [
                'background' => $area->deep_color,
                'foreground' => $area->labelColor(),
            ]]);
            $paymentLabels = [
                'unpaid' => 'Pay later',
                'pending' => 'Payment selected',
                'submitted' => 'Payment submitted',
            ];
            $restoreContext = old('form_context');
            $calendarFormRestore = in_array($restoreContext, ['calendar-create', 'calendar-edit'], true)
                ? [
                    'context' => $restoreContext,
                    'group' => old('editing_group'),
                    'areaIds' => collect(old('living_area_ids', []))->map(fn ($id) => (string) $id)->values()->all(),
                    'guestName' => old('guest_name', auth()->user()->name),
                    'startDate' => old('start_date', ''),
                    'endDate' => old('end_date', ''),
                    'note' => old('note', ''),
                    'paymentMethod' => old('payment_method', 'pay_later'),
                    'paymentReference' => old('payment_reference', ''),
                    'bookAsDraft' => (bool) old('book_as_draft', false),
                ]
                : null;
        @endphp

        <div
            class="grid gap-4 sm:gap-6"
            x-data="calendarBookings({
                bookings: @js($calendarBookingGroups),
                createAction: @js(route('bookings.store', absolute: false)),
                currentMonth: @js($currentMonth),
                defaultGuestName: @js(auth()->user()->name),
                canCreateConfirmedBookings: @js($canCreateConfirmedBookings),
                createUnavailableRanges: @js($unavailableDateRangesByArea),
                initialForm: @js($calendarFormRestore),
            })"
            x-init="init()"
            @date-range-changed="captureDates($event.detail)"
            data-dashboard-layout
        >
                <section data-calendar-overview>
                    <div class="tp-calendar-surface tp-surface tp-surface--action overflow-hidden">
                        <div class="grid grid-cols-[2.75rem_minmax(0,1fr)_2.75rem] items-center gap-2 border-b border-[var(--tp-border)] px-3 py-2.5 md:grid-cols-[3rem_minmax(0,1fr)_3rem] md:px-5 md:py-4">
                            <a href="{{ route('dashboard', ['month' => $previousMonth]) }}" class="tp-calendar-nav-button" aria-label="View previous month">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>

                            <h1 class="text-center tp-heading-page">{{ $monthLabel }}</h1>

                            <a href="{{ route('dashboard', ['month' => $nextMonth]) }}" class="tp-calendar-nav-button justify-self-end" aria-label="View next month">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>

                        </div>

                        <div>
                            <div>
                                <div class="grid grid-cols-7 border-b border-[var(--tp-border)] bg-[var(--tp-surface-muted)] text-center text-[10px] font-semibold uppercase tracking-[0.12em] text-[var(--tp-muted)] md:text-xs md:tracking-[0.18em]">
                                    @foreach ($weekdays as $weekday)
                                        <div class="px-1 py-2 md:px-2 md:py-3">{{ $weekday }}</div>
                                    @endforeach
                                </div>

                                @foreach ($calendarWeeks as $week)
                                    @php
                                        $weekRowHeight = count($week['segments']) > 0
                                            ? 3.75 + ($week['laneCount'] * 1.3)
                                            : 3.75;
                                    @endphp

                                    <div class="tp-calendar-week relative border-b border-[rgba(103,71,43,0.08)] last:border-b-0" style="--tp-calendar-week-min-height: {{ number_format($weekRowHeight, 2, '.', '') }}rem;" data-calendar-week>
                                        <div class="absolute inset-0 grid grid-cols-7">
                                            @foreach ($week['days'] as $day)
                                                <button
                                                    type="button"
                                                    class="group flex h-full items-start justify-start border-r border-[rgba(103,71,43,0.08)] px-2 py-2 text-left transition last:border-r-0 hover:bg-[rgba(26,140,145,0.08)] focus:z-10 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--tp-focus)] md:px-3 md:py-3 {{ $day['isCurrentMonth'] ? 'bg-[var(--tp-surface)]' : 'bg-[var(--tp-surface-muted)] text-[rgba(95,72,56,0.42)]' }} {{ $day['isToday'] ? 'ring-2 ring-inset ring-[rgba(221,79,22,0.35)]' : '' }}"
                                                    aria-label="{{ count($day['bookingGroups']) > 0 ? 'View '.count($day['bookingGroups']).' bookings on ' : 'Create booking starting ' }}{{ $day['date']->format('F j, Y') }}"
                                                    aria-haspopup="dialog"
                                                    data-calendar-day
                                                    data-calendar-date="{{ $day['date']->toDateString() }}"
                                                    @click="openDay(@js($day['date']->toDateString()), @js($day['bookingGroups']), $el)"
                                                >
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="text-xs font-semibold tabular-nums md:text-sm {{ $day['isToday'] ? 'text-[var(--tp-text-accent)]' : 'text-[var(--tp-bark)]' }}">{{ $day['date']->day }}</span>
                                                        @if (! $day['isToday'])
                                                            <span class="text-base font-semibold text-[var(--tp-accent)] opacity-0 transition group-hover:opacity-100 group-focus:opacity-100" aria-hidden="true">+</span>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>

                                        @if (count($week['segments']) > 0)
                                        <div class="pointer-events-none absolute inset-x-0 top-9 px-2 md:top-12 md:px-3">
                                                <div class="grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr)); grid-template-rows: repeat({{ $week['laneCount'] }}, minmax(1.25rem, auto));">
                                                    @foreach ($week['segments'] as $segment)
                                                        <button
                                                            type="button"
                                                            class="pointer-events-auto w-full truncate rounded-full text-left focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-1 {{ $segment['style'] }}"
                                                            style="grid-column: {{ $segment['column_start'] }} / {{ $segment['column_end'] }}; grid-row: {{ $segment['lane'] }}; {{ $segment['inline_style'] }}"
                                                            title="{{ $segment['title'] }}"
                                                            aria-label="{{ $segment['status_label'] }} booking for {{ $segment['guest_name'] }} in {{ $segment['area_name'] }}, {{ $segment['start_date'] }} to {{ $segment['end_date'] }}"
                                                            aria-haspopup="dialog"
                                                            data-calendar-booking-trigger
                                                            data-booking-group="{{ $segment['booking_group'] }}"
                                                            @click="openBooking(@js($segment['booking_group']), $el)"
                                                        >
                                                            {{ $segment['label'] }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" class="tp-button-primary w-full sm:w-auto" data-new-booking @click="openCreate('', $el)">
                            New booking
                        </button>
                    </div>
                </section>

                <template x-teleport="body">
                <div
                    x-cloak
                    x-show="mode !== null"
                    @keydown.escape.window="handleEscape()"
                    class="fixed inset-0 z-50 flex items-end px-4 sm:items-center sm:justify-center sm:p-6"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="calendar-modal-title"
                    data-calendar-modal
                >
                    <button type="button" class="absolute inset-0 bg-[rgba(78,59,46,0.55)]" aria-label="Close calendar dialog" data-calendar-modal-backdrop @click="closeModal()"></button>

                    <div x-ref="dialog" class="relative flex max-h-[96dvh] w-full flex-col overflow-hidden rounded-t-[1.5rem] bg-[var(--tp-paper-soft)] shadow-2xl sm:max-h-[90vh] sm:max-w-2xl sm:rounded-[1.5rem]" @keydown.tab="trapFocus($event)">
                        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-[var(--tp-border)] px-5 py-4 sm:px-6">
                            <div class="flex min-w-0 items-start gap-3">
                                <button x-show="canGoBack" type="button" class="tp-calendar-nav-button -ml-2 shrink-0" aria-label="Back to day agenda" @click="backToAgenda()">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="min-w-0">
                                    <p x-show="mode !== 'form'" class="tp-meta" x-text="mode === 'agenda' ? 'Day agenda' : 'Booking details'"></p>
                                    <h2 x-ref="modalTitle" id="calendar-modal-title" tabindex="-1" class="truncate tp-heading-section" :class="mode === 'form' ? '' : 'mt-1'" x-text="modalTitle"></h2>
                                </div>
                            </div>
                            <button x-ref="closeButton" type="button" class="tp-calendar-nav-button shrink-0" aria-label="Close calendar dialog" data-calendar-modal-close @click="closeModal()">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </button>
                        </header>

                        <div class="overflow-y-auto px-5 py-5 sm:px-6 sm:py-6">
                            <section x-show="mode === 'agenda'" data-calendar-day-agenda>
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-sm leading-6 text-[var(--tp-muted)]" x-text="`${selectedDayBookings.length} ${selectedDayBookings.length === 1 ? 'booking' : 'bookings'}`"></p>
                                    <button type="button" class="tp-button-primary shrink-0" @click="openCreate(selectedDay, null, true)">New booking</button>
                                </div>

                                <div class="mt-5 grid gap-3">
                                    <template x-for="booking in selectedDayBookings" :key="booking.group">
                                        <button type="button" class="rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-surface)] p-4 text-left transition hover:border-[var(--tp-border-strong)] hover:bg-[var(--tp-surface-raised)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)]" @click="openBooking(booking.group, null, true)">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <p class="font-semibold text-[var(--tp-bark)]" x-text="booking.guestName"></p>
                                                    <p class="mt-1 text-sm text-[var(--tp-muted)]"><span x-text="booking.formattedStartDate"></span> – <span x-text="booking.formattedEndDate"></span></p>
                                                </div>
                                                <span class="tp-chip" x-text="booking.statusLabel"></span>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <template x-for="area in booking.areas" :key="area.name">
                                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[var(--tp-muted)]">
                                                        <span aria-hidden="true" class="h-2.5 w-2.5 rounded-full" :style="`background-color: ${area.color}`"></span>
                                                        <span x-text="area.name"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </section>

                            <section x-show="mode === 'view'" data-calendar-booking-details>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="area in (selectedBooking?.areas ?? [])" :key="area.name">
                                        <span class="tp-chip border-transparent" :style="`background-color: ${area.color}; color: ${area.labelColor}`" x-text="area.name"></span>
                                    </template>
                                </div>
                                <dl class="mt-5 space-y-3 text-sm">
                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-[var(--tp-muted)]">Dates</dt>
                                        <dd class="text-right font-semibold text-[var(--tp-bark)]"><span x-text="selectedBooking?.formattedStartDate"></span> – <span x-text="selectedBooking?.formattedEndDate"></span></dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-[var(--tp-muted)]">Status</dt>
                                        <dd class="font-semibold text-[var(--tp-bark)]" x-text="selectedBooking?.statusLabel"></dd>
                                    </div>
                                </dl>
                                <p class="mt-6 rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-surface-muted)] px-4 py-3 text-sm leading-6 text-[var(--tp-muted)]">You can view this booking, but you do not have permission to edit it.</p>
                            </section>

                            <form x-show="mode === 'form'" method="POST" :action="form.action" class="space-y-5" data-calendar-booking-form>
                                @csrf
                                <input type="hidden" name="_method" value="PATCH" :disabled="! form.isEdit">
                                <input type="hidden" name="return_month" value="{{ $currentMonth }}">
                                <input type="hidden" name="form_context" :value="form.isEdit ? 'calendar-edit' : 'calendar-create'">
                                <input type="hidden" name="editing_group" :value="form.group" :disabled="! form.isEdit">

                                @if ($errors->any())
                                    <x-error-summary :errors="$errors" class="mb-5" />
                                @endif

                                <fieldset @if ($errors->has('living_area_ids') || $errors->has('living_area_ids.*')) aria-describedby="calendar_living_areas_error" @endif>
                                    <legend class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--tp-muted)]">Parts</legend>
                                    <p class="mt-1 text-xs text-[var(--tp-muted)]" x-text="form.lockAreas ? 'Poobahs cannot change the parts on a confirmed stay.' : 'Choose one or more.'"></p>
                                    <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                        @foreach ($livingAreas as $area)
                                            <label class="flex min-h-12 items-center gap-2 rounded-[0.9rem] border border-[var(--tp-border)] bg-[var(--tp-control)] px-3 py-2.5 shadow-sm transition hover:border-[var(--tp-border-strong)]">
                                                <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" x-model="form.areaIds" :disabled="form.lockAreas" class="h-4 w-4 shrink-0 rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] focus:ring-[var(--tp-focus)]">
                                                <span class="min-w-0 truncate text-sm font-semibold text-[var(--tp-bark)]">
                                                    <span aria-hidden="true" class="mr-1 inline-flex h-3 w-3 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                    {{ str_ends_with($area->name, ' Part') ? substr($area->name, 0, -5) : $area->name }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <template x-if="form.lockAreas">
                                        <div>
                                            <template x-for="areaId in form.areaIds" :key="areaId">
                                                <input type="hidden" name="living_area_ids[]" :value="areaId">
                                            </template>
                                        </div>
                                    </template>
                                    <x-input-error id="calendar_living_areas_error" :messages="$errors->get('living_area_ids')" class="mt-2" />
                                </fieldset>

                                <div>
                                    <x-input-label for="calendar_guest_name" :value="__('Guest Name')" />
                                    <x-text-input id="calendar_guest_name" name="guest_name" type="text" class="mt-2 w-full" x-model="form.guestName" required :invalid="$errors->has('guest_name')" error-id="calendar_guest_name_error" />
                                    <x-input-error id="calendar_guest_name_error" :messages="$errors->get('guest_name')" class="mt-2" />
                                </div>

                                <x-date-range-picker
                                    id="calendar_booking_dates"
                                    label="Stay Dates"
                                    start-name="start_date"
                                    end-name="end_date"
                                    :disabled-ranges-by-area="$unavailableDateRangesByArea"
                                    :invalid="$errors->has('start_date') || $errors->has('end_date')"
                                    error-id="calendar_booking_dates_error"
                                    required
                                />
                                <x-input-error id="calendar_booking_dates_error" :messages="array_merge($errors->get('start_date'), $errors->get('end_date'))" class="mt-2" />

                                <div>
                                    <x-input-label for="calendar_note" :value="__('Note')" />
                                    <textarea id="calendar_note" name="note" rows="3" x-model="form.note" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-control)] px-4 py-3 text-[var(--tp-bark)] shadow-sm placeholder:text-[var(--tp-muted)] focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]" placeholder="Optional note"></textarea>
                                </div>

                                <details class="group rounded-[1rem] border-2 border-[var(--tp-olive)] bg-transparent text-sm leading-6 text-[var(--tp-muted)]">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 font-semibold text-[var(--tp-bark)] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--tp-focus)]">
                                        View pricing
                                        <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                    </summary>
                                    <ul class="list-disc space-y-1 px-4 pb-4 pl-9">
                                        <li>$10 per night if you are the Poobah for the living area you are booking.</li>
                                        <li>$20 per night for standard bookings.</li>
                                        <li>$500 for booking all four living areas for a full 7-night week.</li>
                                    </ul>
                                </details>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="calendar_payment_method" :value="__('Payment Method')" />
                                        <select id="calendar_payment_method" name="payment_method" x-model="form.paymentMethod" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-control)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">
                                            @foreach ($paymentMethods as $methodValue => $methodLabel)
                                                <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <x-input-label for="calendar_payment_reference" :value="__('Payment Reference')" />
                                        <x-text-input id="calendar_payment_reference" name="payment_reference" type="text" class="mt-2 w-full" x-model="form.paymentReference" />
                                    </div>
                                </div>

                                @if ($canCreateConfirmedBookings)
                                    <label x-show="! form.isEdit" class="flex items-start gap-3 rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-surface-raised)] px-4 py-4 text-sm text-[var(--tp-bark)]">
                                        <input type="checkbox" name="book_as_draft" value="1" x-model="form.bookAsDraft" :disabled="form.isEdit" class="mt-1 h-4 w-4 rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] focus:ring-[var(--tp-focus)]">
                                        <span><span class="block font-semibold">Book as draft</span><span class="mt-1 block leading-6 text-[var(--tp-muted)]">Use this when the booking should wait for approval instead of going live right away.</span></span>
                                    </label>
                                @endif

                                <x-primary-button class="w-full justify-center"><span x-text="form.isEdit ? 'Save booking' : @js($canCreateConfirmedBookings ? 'Save booking' : 'Submit booking')"></span></x-primary-button>
                            </form>
                        </div>
                    </div>
                </div>
                </template>

                <section class="tp-surface tp-surface--booking px-5 py-6 sm:p-6" data-your-bookings>
                    <h2 class="tp-heading-section">Your bookings</h2>

                    <div class="mt-5 space-y-4 sm:mt-6">
                        @forelse ($myBookings as $booking)
                            <article class="tp-surface-subtle p-4 sm:p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($booking['areas'] as $areaName)
                                                <span class="tp-chip border-transparent" style="background-color: {{ $livingAreaColorMap[$areaName]['background'] ?? 'var(--tp-brown-strong)' }}; color: {{ $livingAreaColorMap[$areaName]['foreground'] ?? '#fffdf5' }};">{{ $areaName }}</span>
                                            @endforeach
                                        </div>
                                        <div>
                                            <h3 class="tp-heading-item">{{ $booking['guest_name'] }}</h3>
                                            <p class="mt-1 text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['start_date']->format('M j, Y') }} to {{ $booking['end_date']->format('M j, Y') }}</p>
                                            @if ($booking['note'])
                                                <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['note'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="min-w-56 space-y-2 rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-surface)] p-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="tp-meta">Status</span>
                                            <span class="text-xs font-semibold uppercase tracking-[0.16em] {{ $booking['status'] === 'active' ? 'text-[var(--tp-status)]' : 'rounded-full border border-dashed border-[var(--tp-border)] bg-[var(--tp-surface-muted)] px-3 py-2 text-[var(--tp-muted)]' }}">{{ ucfirst($booking['status']) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="tp-meta">Amount</span>
                                            <span class="text-sm font-semibold text-[var(--tp-bark)]">${{ number_format(($booking['amount_cents'] ?? 0) / 100, 0) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="tp-meta">Payment</span>
                                            <span class="text-sm font-semibold text-[var(--tp-bark)]">{{ $paymentLabels[$booking['payment_status']] ?? ucfirst($booking['payment_status']) }}</span>
                                        </div>
                                    </div>
                                </div>

                                @if ($booking['can_update_payment'])
                                    <form method="POST" action="{{ route('bookings.payment.update', $booking['group']) }}" class="mt-5 grid gap-4 border-t border-[var(--tp-border)] pt-5 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                                        @csrf
                                        @method('PATCH')

                                        <div>
                                            <x-input-label for="payment_method_{{ $booking['group'] }}" :value="__('Payment Method')" />
                                            <select id="payment_method_{{ $booking['group'] }}" name="payment_method" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-control)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">
                                                @foreach ($paymentMethods as $methodValue => $methodLabel)
                                                    <option value="{{ $methodValue }}" @selected(old('payment_method', $booking['payment_method']) === $methodValue)>{{ $methodLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <x-primary-button class="justify-center">{{ __('Update Payment') }}</x-primary-button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-[1rem] border border-dashed border-[var(--tp-border)] bg-[var(--tp-surface-muted)] px-5 py-7 text-center text-sm leading-6 text-[var(--tp-muted)]">
                                No bookings yet. Choose a date on the calendar to request a stay.
                            </div>
                        @endforelse
                    </div>
                </section>
        </div>
    </div>
</x-app-layout>
