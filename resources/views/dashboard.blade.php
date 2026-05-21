<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap gap-2">
            <div class="flex flex-wrap gap-2">
                @foreach ($livingAreas as $area)
                    <span class="tp-chip border-transparent" style="background-color: {{ $area['soft_color'] }}; color: {{ $area['deep_color'] }};">
                        {{ $area['name'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-0 py-6 sm:px-6 lg:px-8">
        @php
            $monthDate = \Carbon\CarbonImmutable::createFromFormat('F Y', $monthLabel, config('app.timezone'));
            $previousMonth = $monthDate->subMonth()->format('Y-m');
            $nextMonth = $monthDate->addMonth()->format('Y-m');
            $paymentLabels = [
                'unpaid' => 'Pay later',
                'pending' => 'Payment selected',
                'submitted' => 'Payment submitted',
            ];
        @endphp

        <div class="space-y-6">
            @if (session('status'))
                <div class="rounded-[1rem] border border-[rgba(88,109,91,0.18)] bg-[rgba(88,109,91,0.08)] px-5 py-4 text-sm font-semibold text-[var(--tp-pine)]">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-[1rem] border border-[rgba(152,97,70,0.18)] bg-[rgba(152,97,70,0.08)] px-5 py-4 text-sm text-[var(--tp-bark)]">
                    <p class="font-semibold">Your booking could not be saved.</p>
                    <ul class="mt-2 space-y-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                <section class="space-y-6">
                    <div class="tp-surface overflow-hidden rounded-none sm:rounded-[1.5rem]">
                        <div class="flex flex-col gap-4 border-b border-[var(--tp-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <p class="tp-meta">Calendar</p>
                                <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)] sm:text-3xl">{{ $monthLabel }}</h3>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('dashboard', ['month' => $previousMonth]) }}" class="tp-button-secondary px-4 py-2">Previous</a>
                                <a href="{{ route('dashboard', ['month' => $nextMonth]) }}" class="tp-button-secondary px-4 py-2">Next</a>
                            </div>
                        </div>

                        <div>
                            <div>
                                <div class="grid grid-cols-7 border-b border-[var(--tp-border)] bg-[rgba(220,236,241,0.68)] text-center text-[10px] font-semibold uppercase tracking-[0.12em] text-[var(--tp-muted)] sm:text-xs sm:tracking-[0.18em]">
                                    @foreach ($weekdays as $weekday)
                                        <div class="px-1 py-2 sm:px-2 sm:py-3">{{ $weekday }}</div>
                                    @endforeach
                                </div>

                                @foreach ($calendarWeeks as $week)
                                    @php
                                        $weekRowHeight = count($week['segments']) > 0
                                            ? 3.5 + ($week['laneCount'] * 1.8)
                                            : 3.5;
                                    @endphp

                                    <div class="relative border-b border-[rgba(31,52,64,0.08)] last:border-b-0" style="min-height: {{ number_format($weekRowHeight, 2, '.', '') }}rem;">
                                        <div class="grid h-full grid-cols-7">
                                            @foreach ($week['days'] as $day)
                                                <div class="h-full border-r border-[rgba(31,52,64,0.08)] px-1 py-2 align-top last:border-r-0 sm:px-3 sm:py-3 {{ $day['isCurrentMonth'] ? 'bg-[rgba(248,252,252,0.8)]' : 'bg-[rgba(220,236,241,0.34)] text-[rgba(31,52,64,0.42)]' }} {{ $day['isToday'] ? 'ring-2 ring-inset ring-[rgba(108,135,148,0.55)]' : '' }}">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <span class="font-display text-base sm:text-xl {{ $day['isToday'] ? 'text-[var(--tp-brass)]' : 'text-[var(--tp-bark)]' }}">{{ $day['date']->day }}</span>
                                                        @if ($day['isToday'])
                                                            <span class="hidden rounded-full bg-[var(--tp-bark)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--tp-paper-soft)] sm:inline-flex">Today</span>
                                                            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-[var(--tp-bark)] sm:hidden"></span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if (count($week['segments']) > 0)
                                            <div class="pointer-events-none absolute inset-x-0 bottom-1.5 px-1.5 sm:bottom-2 sm:px-2">
                                                <div class="grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr)); grid-template-rows: repeat({{ $week['laneCount'] }}, minmax(1.5rem, auto));">
                                                    @foreach ($week['segments'] as $segment)
                                                        <div class="pointer-events-auto rounded-full {{ $segment['style'] }}" style="grid-column: {{ $segment['column_start'] }} / {{ $segment['column_end'] }}; grid-row: {{ $segment['lane'] }}; {{ $segment['inline_style'] }}" title="{{ $segment['title'] }}">
                                                            {{ $segment['label'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <section class="tp-surface p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="tp-meta">Your bookings</p>
                                <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">Drafts and stays</h3>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse ($myBookings as $booking)
                                <article class="tp-surface-subtle p-5">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($booking['areas'] as $areaName)
                                                    <span class="tp-chip">{{ $areaName }}</span>
                                                @endforeach
                                            </div>
                                            <div>
                                                <h4 class="font-display text-2xl text-[var(--tp-bark)]">{{ $booking['guest_name'] }}</h4>
                                                <p class="mt-1 text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['start_date']->format('M j, Y') }} to {{ $booking['end_date']->format('M j, Y') }}</p>
                                                @if ($booking['note'])
                                                    <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['note'] }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="min-w-56 space-y-2 rounded-[1rem] bg-[rgba(220,236,241,0.58)] p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="tp-meta">Status</span>
                                                <span class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] {{ $booking['status'] === 'active' ? 'bg-[var(--tp-pine)] text-white' : 'border border-dashed border-[var(--tp-border)] bg-[rgba(253,251,247,0.72)] text-[var(--tp-muted)]' }}">{{ ucfirst($booking['status']) }}</span>
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
                                        <form method="POST" action="{{ route('bookings.payment.update', $booking['group']) }}" class="mt-5 grid gap-4 border-t border-[var(--tp-border)] pt-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label for="payment_method_{{ $booking['group'] }}" :value="__('Payment Method')" />
                                                <select id="payment_method_{{ $booking['group'] }}" name="payment_method" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.9)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-bark)] focus:ring-[rgba(108,135,148,0.35)]">
                                                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                                                        <option value="{{ $methodValue }}" @selected(old('payment_method', $booking['payment_method']) === $methodValue)>{{ $methodLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="payment_reference_{{ $booking['group'] }}" :value="__('Payment Reference')" />
                                                <x-text-input id="payment_reference_{{ $booking['group'] }}" name="payment_reference" type="text" class="mt-2 w-full" :value="old('payment_reference', $booking['payment_reference'])" placeholder="Optional reference" />
                                            </div>

                                            <x-primary-button class="justify-center">{{ __('Update Payment') }}</x-primary-button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <div class="rounded-[1rem] border border-dashed border-[var(--tp-border)] bg-[rgba(253,251,247,0.55)] px-5 py-8 text-center text-sm leading-6 text-[var(--tp-muted)]">
                                    No bookings yet. Use the form to request dates.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </section>

                <aside class="space-y-6">
                    <section class="tp-surface p-6">
                        <p class="tp-meta">Request a stay</p>
                        <h3 class="mt-2 font-display text-3xl text-[var(--tp-bark)]">Create a draft booking</h3>

                        <form method="POST" action="{{ route('bookings.store') }}" class="mt-6 space-y-5">
                            @csrf

                            <fieldset>
                                <legend class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--tp-muted)]">Living areas</legend>
                                <div class="mt-3 space-y-3">
                                    @foreach ($livingAreas as $area)
                                        <label class="flex items-start gap-3 rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(248,252,252,0.8)] px-4 py-3 shadow-sm transition hover:border-[var(--tp-border-strong)]">
                                            <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" class="mt-1 h-4 w-4 rounded border-[var(--tp-border-strong)] text-[var(--tp-bark)] focus:ring-[rgba(108,135,148,0.35)]" @checked(collect(old('living_area_ids', []))->contains($area->id))>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center gap-2 font-semibold text-[var(--tp-bark)]">
                                                    <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                    {{ $area->name }}
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

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="start_date" :value="__('Start Date')" />
                                    <x-text-input id="start_date" name="start_date" type="date" class="mt-2 w-full" :value="old('start_date')" required />
                                </div>

                                <div>
                                    <x-input-label for="end_date" :value="__('End Date')" />
                                    <x-text-input id="end_date" name="end_date" type="date" class="mt-2 w-full" :value="old('end_date')" required />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="note" :value="__('Note')" />
                                <textarea id="note" name="note" rows="4" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.9)] px-4 py-3 text-[var(--tp-bark)] shadow-sm placeholder:text-[var(--tp-muted)] focus:border-[var(--tp-bark)] focus:ring-[rgba(108,135,148,0.35)]" placeholder="Optional note">{{ old('note') }}</textarea>
                            </div>

                            <div class="rounded-[1rem] bg-[rgba(220,236,241,0.58)] p-4 text-sm leading-6 text-[var(--tp-muted)]">
                                <p class="font-semibold text-[var(--tp-bark)]">Pricing</p>
                                <ul class="mt-2 space-y-1">
                                    <li>$10 per night if you are the Poobah for the living area you are booking.</li>
                                    <li>$20 per night for standard bookings.</li>
                                    <li>$500 for booking all four living areas for a full 7-night week.</li>
                                </ul>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="payment_method" :value="__('Payment Method')" />
                                    <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.9)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-bark)] focus:ring-[rgba(108,135,148,0.35)]">
                                        @foreach ($paymentMethods as $methodValue => $methodLabel)
                                            <option value="{{ $methodValue }}" @selected(old('payment_method', 'pay_later') === $methodValue)>{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="payment_reference" :value="__('Payment Reference')" />
                                    <x-text-input id="payment_reference" name="payment_reference" type="text" class="mt-2 w-full" :value="old('payment_reference')" placeholder="Optional reference" />
                                </div>
                            </div>

                            <x-primary-button class="w-full justify-center">{{ __('Submit Booking') }}</x-primary-button>
                        </form>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
