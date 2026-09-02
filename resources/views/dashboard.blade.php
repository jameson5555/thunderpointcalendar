<x-app-layout>
    <x-slot name="header">
        <div class="tp-part-legend" aria-label="Part color legend">
            @foreach ($livingAreas as $area)
                @php
                    $legendName = str_ends_with($area['name'], ' Part')
                        ? substr($area['name'], 0, -5)
                        : $area['name'];
                @endphp
                <span class="tp-part-legend-item">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $area['deep_color'] }};"></span>
                    <span>{{ $legendName }}</span>
                </span>
            @endforeach
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-0 py-2 sm:px-6 sm:py-6 lg:px-8">
        @php
            $monthDate = \Carbon\CarbonImmutable::createFromFormat('F Y', $monthLabel, config('app.timezone'));
            $previousMonth = $monthDate->subMonth()->format('Y-m');
            $nextMonth = $monthDate->addMonth()->format('Y-m');
            $livingAreaColorMap = $livingAreas->mapWithKeys(fn ($area) => [$area->name => $area->deep_color]);
            $paymentLabels = [
                'unpaid' => 'Pay later',
                'pending' => 'Payment selected',
                'submitted' => 'Payment submitted',
            ];
        @endphp

        <div>
            <div class="grid gap-4 sm:gap-6 xl:grid-cols-[24rem_minmax(0,1fr)]">
                <section class="order-1 xl:col-start-2 xl:row-start-1" x-data="calendarBookingDetails()">
                    <div class="tp-surface overflow-hidden rounded-none sm:rounded-[1.5rem]">
                        <div class="grid grid-cols-[2.75rem_minmax(0,1fr)_2.75rem] items-center border-b border-[var(--tp-border)] px-3 py-2.5 sm:grid-cols-[3rem_minmax(0,1fr)_3rem] sm:px-5 sm:py-4">
                            <a href="{{ route('dashboard', ['month' => $previousMonth]) }}" class="tp-calendar-nav-button" aria-label="View previous month">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>

                            <h2 class="text-center font-display text-xl text-[var(--tp-bark)] sm:text-3xl">{{ $monthLabel }}</h2>

                            <a href="{{ route('dashboard', ['month' => $nextMonth]) }}" class="tp-calendar-nav-button justify-self-end" aria-label="View next month">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </a>
                        </div>

                        <div>
                            <div>
                                <div class="grid grid-cols-7 border-b border-[var(--tp-border)] bg-[rgba(247,240,215,0.72)] text-center text-[10px] font-semibold uppercase tracking-[0.12em] text-[var(--tp-muted)] sm:text-xs sm:tracking-[0.18em]">
                                    @foreach ($weekdays as $weekday)
                                        <div class="px-1 py-2 sm:px-2 sm:py-3">{{ $weekday }}</div>
                                    @endforeach
                                </div>

                                @foreach ($calendarWeeks as $week)
                                    @php
                                        $weekRowHeight = count($week['segments']) > 0
                                            ? 3.75 + ($week['laneCount'] * 1.3)
                                            : 3.75;
                                    @endphp

                                    <div class="relative border-b border-[rgba(103,71,43,0.08)] last:border-b-0" style="height: {{ number_format($weekRowHeight, 2, '.', '') }}rem;">
                                        <div class="absolute inset-0 grid grid-cols-7">
                                            @foreach ($week['days'] as $day)
                                                <div class="h-full border-r border-[rgba(103,71,43,0.08)] px-2 py-3 align-top last:border-r-0 sm:px-3 sm:py-3.5 {{ $day['isCurrentMonth'] ? 'bg-[rgba(255,252,245,0.92)]' : 'bg-[rgba(247,240,215,0.62)] text-[rgba(95,72,56,0.42)]' }} {{ $day['isToday'] ? 'ring-2 ring-inset ring-[rgba(221,79,22,0.35)]' : '' }}">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="text-xs font-semibold tabular-nums sm:text-sm {{ $day['isToday'] ? 'text-[var(--tp-brass)]' : 'text-[var(--tp-bark)]' }}">{{ $day['date']->day }}</span>
                                                        @if ($day['isToday'])
                                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[var(--tp-accent)]"></span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if (count($week['segments']) > 0)
                                            <div class="pointer-events-none absolute inset-x-0 bottom-2 px-2 sm:bottom-2.5 sm:px-3">
                                                <div class="grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr)); grid-template-rows: repeat({{ $week['laneCount'] }}, minmax(1.25rem, auto));">
                                                    @foreach ($week['segments'] as $segment)
                                                        <button
                                                            type="button"
                                                            class="pointer-events-auto w-full truncate rounded-full text-left focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-1 {{ $segment['style'] }}"
                                                            style="grid-column: {{ $segment['column_start'] }} / {{ $segment['column_end'] }}; grid-row: {{ $segment['lane'] }}; {{ $segment['inline_style'] }}"
                                                            title="{{ $segment['title'] }}"
                                                            aria-haspopup="dialog"
                                                            data-calendar-booking-trigger
                                                            @click="openDetails(@js([
                                                                'areaName' => $segment['area_name'],
                                                                'areaColor' => $segment['area_color'],
                                                                'guestName' => $segment['guest_name'],
                                                                'startDate' => $segment['start_date'],
                                                                'endDate' => $segment['end_date'],
                                                                'statusLabel' => $segment['status_label'],
                                                            ]), $el)"
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

                    <div class="px-4 py-3 sm:hidden">
                        <a href="#booking-form" class="tp-button-primary w-full" data-booking-jump>Book dates</a>
                    </div>

                    <div
                        x-cloak
                        x-show="isOpen"
                        @keydown.escape.window="closeDetails()"
                        class="fixed inset-0 z-50 flex items-end sm:items-center sm:justify-center sm:p-6"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="calendar-booking-title"
                        data-calendar-booking-dialog
                    >
                        <button type="button" class="absolute inset-0 bg-[rgba(78,59,46,0.5)]" aria-label="Close booking details" data-calendar-booking-backdrop @click="closeDetails()"></button>

                        <div x-ref="dialog" class="relative w-full rounded-t-[1.5rem] bg-[var(--tp-paper-soft)] p-5 shadow-2xl sm:max-w-md sm:rounded-[1.5rem]" @keydown.tab="trapFocus($event)">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="tp-meta">Booking details</p>
                                    <h3 id="calendar-booking-title" class="mt-2 font-display text-2xl text-[var(--tp-bark)]" x-text="selectedBooking?.guestName"></h3>
                                </div>
                                <button x-ref="closeButton" type="button" class="tp-calendar-nav-button shrink-0" aria-label="Close booking details" data-calendar-booking-close @click="closeDetails()">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>

                            <dl class="mt-5 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-[var(--tp-muted)]">Part</dt>
                                    <dd class="flex items-center gap-2 font-semibold text-[var(--tp-bark)]">
                                        <span class="h-3 w-3 rounded-full" :style="`background-color: ${selectedBooking?.areaColor}`"></span>
                                        <span x-text="selectedBooking?.areaName"></span>
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-[var(--tp-muted)]">Dates</dt>
                                    <dd class="text-right font-semibold text-[var(--tp-bark)]"><span x-text="selectedBooking?.startDate"></span> – <span x-text="selectedBooking?.endDate"></span></dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-[var(--tp-muted)]">Status</dt>
                                    <dd class="font-semibold text-[var(--tp-bark)]" x-text="selectedBooking?.statusLabel"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                </section>

                <aside id="booking-form" class="order-2 scroll-mt-4 xl:col-start-1 xl:row-start-1" data-booking-form>
                    <section class="tp-surface px-5 py-6 sm:p-6">
                        <h2 class="font-display text-2xl text-[var(--tp-bark)] sm:text-3xl">Book dates</h2>

                        @if ($canCreateConfirmedBookings)
                            <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">Your bookings are confirmed by default here unless you mark them as draft.</p>
                        @endif

                        <form method="POST" action="{{ route('bookings.store') }}" class="mt-5 space-y-4 sm:mt-6 sm:space-y-5">
                            @csrf

                            <fieldset>
                                <legend class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--tp-muted)]">Parts</legend>
                                <p class="mt-1 text-xs text-[var(--tp-muted)]">Choose one or more.</p>
                                <div class="mt-3 grid grid-cols-2 gap-2 xl:grid-cols-1 xl:gap-3">
                                    @foreach ($livingAreas as $area)
                                        @php
                                            $partName = str_ends_with($area->name, ' Part')
                                                ? substr($area->name, 0, -5)
                                                : $area->name;
                                        @endphp
                                        <label class="flex min-h-12 items-center gap-2 rounded-[0.9rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.84)] px-3 py-2.5 shadow-sm transition hover:border-[var(--tp-border-strong)] xl:gap-3 xl:px-4 xl:py-3">
                                            <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" class="h-4 w-4 shrink-0 rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] focus:ring-[var(--tp-focus)]" @checked(collect(old('living_area_ids', []))->contains($area->id))>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex min-w-0 items-center gap-1.5 text-sm font-semibold text-[var(--tp-bark)] xl:gap-2 xl:text-base">
                                                    <span class="inline-flex h-3 w-3 shrink-0 rounded-full xl:h-3.5 xl:w-3.5" style="background-color: {{ $area->deep_color }};"></span>
                                                    <span class="truncate xl:hidden">{{ $partName }}</span>
                                                    <span class="hidden truncate xl:inline">{{ $area->name }}</span>
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div>
                                <x-input-label for="guest_name" :value="__('Guest Name')" />
                                <x-text-input id="guest_name" name="guest_name" type="text" class="mt-2 w-full" :value="old('guest_name', auth()->user()->name)" required />
                            </div>

                            <x-date-range-picker
                                id="booking_dates"
                                label="Stay Dates"
                                start-name="start_date"
                                end-name="end_date"
                                :start-value="old('start_date')"
                                :end-value="old('end_date')"
                                :disabled-ranges-by-area="$unavailableDateRangesByArea"
                                required
                            />

                            <div>
                                <x-input-label for="note" :value="__('Note')" />
                                <textarea id="note" name="note" rows="3" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm placeholder:text-[var(--tp-muted)] focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]" placeholder="Optional note">{{ old('note') }}</textarea>
                            </div>

                            <details class="group rounded-[1rem] border-2 border-[var(--tp-olive)] bg-transparent text-sm leading-6 text-[var(--tp-muted)]">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 font-semibold text-[var(--tp-bark)] focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[var(--tp-focus)]">
                                    View pricing
                                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </summary>
                                <ul class="list-disc space-y-1 px-4 pb-4 pl-9">
                                    <li>$10 per night if you are the Poobah for the living area you are booking.</li>
                                    <li>$20 per night for standard bookings.</li>
                                    <li>$500 for booking all four living areas for a full 7-night week.</li>
                                </ul>
                            </details>

                            <div>
                                <x-input-label for="payment_method" :value="__('Payment Method')" />
                                <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">
                                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                                        <option value="{{ $methodValue }}" @selected(old('payment_method', 'pay_later') === $methodValue)>{{ $methodLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($canCreateConfirmedBookings)
                                <label class="flex items-start gap-3 rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.76)] px-4 py-4 text-sm text-[var(--tp-bark)]">
                                    <input type="checkbox" name="book_as_draft" value="1" class="mt-1 h-4 w-4 rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] focus:ring-[var(--tp-focus)]" @checked(old('book_as_draft'))>
                                    <span>
                                        <span class="block font-semibold">Book as draft</span>
                                        <span class="mt-1 block leading-6 text-[var(--tp-muted)]">Use this when the booking should wait for approval instead of going live right away.</span>
                                    </span>
                                </label>
                            @endif

                            <x-primary-button class="w-full justify-center">{{ __($canCreateConfirmedBookings ? 'Save Booking' : 'Submit Booking') }}</x-primary-button>
                        </form>
                    </section>
                </aside>

                <section class="order-3 tp-surface px-5 py-6 sm:p-6 xl:col-start-2 xl:row-start-2" data-your-bookings>
                    <h2 class="font-display text-2xl text-[var(--tp-bark)]">Your bookings</h2>

                    <div class="mt-5 space-y-4 sm:mt-6">
                        @forelse ($myBookings as $booking)
                            <article class="tp-surface-subtle p-4 sm:p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-3">
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($booking['areas'] as $areaName)
                                                <span class="tp-chip border-transparent text-white" style="background-color: {{ $livingAreaColorMap[$areaName] ?? 'var(--tp-brown)' }};">{{ $areaName }}</span>
                                            @endforeach
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-[var(--tp-bark)]">{{ $booking['guest_name'] }}</h3>
                                            <p class="mt-1 text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['start_date']->format('M j, Y') }} to {{ $booking['end_date']->format('M j, Y') }}</p>
                                            @if ($booking['note'])
                                                <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['note'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="min-w-56 space-y-2 rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,252,245,0.92)] p-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="tp-meta">Status</span>
                                            <span class="text-xs font-semibold uppercase tracking-[0.16em] {{ $booking['status'] === 'active' ? 'text-[var(--tp-pine)]' : 'rounded-full border border-dashed border-[var(--tp-border)] bg-[rgba(253,251,247,0.72)] px-3 py-2 text-[var(--tp-muted)]' }}">{{ ucfirst($booking['status']) }}</span>
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
                                            <select id="payment_method_{{ $booking['group'] }}" name="payment_method" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">
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
                            <div class="rounded-[1rem] border border-dashed border-[var(--tp-border)] bg-[rgba(253,251,247,0.55)] px-5 py-7 text-center text-sm leading-6 text-[var(--tp-muted)]">
                                No bookings yet. Use the form to request dates.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
