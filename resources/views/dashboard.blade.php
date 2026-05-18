<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--tp-pine)]">Calendar</p>
                <h2 class="mt-2 font-display text-3xl leading-tight text-[var(--tp-bark)] sm:text-4xl">
                    {{ $monthLabel }} at Thunderpoint
                </h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-[rgba(61,52,39,0.72)] sm:text-base">All four living areas stay visible in one place. Draft and active bookings will live here, with approvals and payment status layered in next.</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach ($livingAreas as $area)
                    <span class="tp-chip border-transparent" style="background-color: {{ $area['soft_color'] }}; color: {{ $area['deep_color'] }};">
                        {{ $area['name'] }}
                    </span>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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
                <div class="rounded-[1.4rem] border border-[rgba(49,91,63,0.16)] bg-[rgba(49,91,63,0.08)] px-5 py-4 text-sm font-semibold text-[var(--tp-pine)]">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-[1.4rem] border border-[rgba(158,74,74,0.16)] bg-[rgba(158,74,74,0.08)] px-5 py-4 text-sm text-[var(--tp-bark)]">
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
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($livingAreas as $area)
                            <article class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.8)] p-5 shadow-[0_18px_50px_rgba(61,52,39,0.08)]">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[rgba(61,52,39,0.58)]">Living area</p>
                                        <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">{{ $area->name }}</h3>
                                    </div>
                                    <span class="inline-flex h-4 w-4 rounded-full border border-white/70 shadow-sm" style="background-color: {{ $area->deep_color }};"></span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $area->description() }}</p>
                                <div class="mt-5 flex gap-2 text-xs font-semibold uppercase tracking-[0.16em]">
                                    <span class="rounded-full px-3 py-2" style="background-color: {{ $area->soft_color }}; color: {{ $area->deep_color }};">Active</span>
                                    <span class="rounded-full border border-dashed border-[rgba(61,52,39,0.18)] bg-white/70 px-3 py-2 text-[rgba(61,52,39,0.68)]">Draft</span>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="overflow-hidden rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.84)] shadow-[0_24px_70px_rgba(61,52,39,0.08)]">
                        <div class="flex flex-col gap-4 border-b border-[rgba(61,52,39,0.08)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <h3 class="font-display text-2xl text-[var(--tp-bark)]">Month view</h3>
                                <p class="mt-1 text-sm text-[rgba(61,52,39,0.68)]">All draft and active bookings are visible here for every living area.</p>
                            </div>

                            <div class="flex items-center gap-2">
                                <a href="{{ route('dashboard', ['month' => $previousMonth]) }}" class="rounded-full border border-[rgba(61,52,39,0.14)] bg-white/70 px-4 py-2 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Previous</a>
                                <span class="tp-chip">{{ $monthLabel }}</span>
                                <a href="{{ route('dashboard', ['month' => $nextMonth]) }}" class="rounded-full border border-[rgba(61,52,39,0.14)] bg-white/70 px-4 py-2 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Next</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-7 border-b border-[rgba(61,52,39,0.08)] bg-[rgba(244,237,218,0.9)] text-center text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(61,52,39,0.58)]">
                            @foreach ($weekdays as $weekday)
                                <div class="px-2 py-3">{{ $weekday }}</div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-7">
                            @foreach ($calendarDays as $day)
                                <div class="min-h-32 border-b border-r border-[rgba(61,52,39,0.08)] px-3 py-3 align-top {{ $day['isCurrentMonth'] ? 'bg-[rgba(255,250,240,0.72)]' : 'bg-[rgba(233,224,203,0.42)] text-[rgba(61,52,39,0.48)]' }} {{ $day['isToday'] ? 'ring-2 ring-inset ring-[var(--tp-lake)]' : '' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-display text-xl {{ $day['isToday'] ? 'text-[var(--tp-lake)]' : 'text-[var(--tp-bark)]' }}">{{ $day['date']->day }}</span>
                                        @if ($day['isToday'])
                                            <span class="rounded-full bg-[var(--tp-lake)] px-2 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-white">Today</span>
                                        @endif
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        @forelse ($day['markers'] as $marker)
                                            <div class="rounded-2xl px-3 py-2 text-xs font-semibold leading-5 {{ $marker['style'] }}" @if ($marker['inline_style']) style="{{ $marker['inline_style'] }}" @endif>
                                                {{ $marker['label'] }}
                                            </div>
                                        @empty
                                            <p class="text-xs leading-5 text-[rgba(61,52,39,0.48)]">Open for booking</p>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <section class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-6 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">My bookings</p>
                                <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">Drafts and payment status</h3>
                            </div>
                            <span class="tp-chip">You can pay later and come back any time</span>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse ($myBookings as $booking)
                                <article class="rounded-[1.4rem] border border-[rgba(61,52,39,0.1)] bg-white/75 p-5 shadow-[0_10px_30px_rgba(61,52,39,0.05)]">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                        <div class="space-y-3">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($booking['areas'] as $areaName)
                                                    <span class="tp-chip">{{ $areaName }}</span>
                                                @endforeach
                                            </div>
                                            <div>
                                                <h4 class="font-display text-2xl text-[var(--tp-bark)]">{{ $booking['guest_name'] }}</h4>
                                                <p class="mt-1 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $booking['start_date']->format('M j, Y') }} to {{ $booking['end_date']->format('M j, Y') }}</p>
                                                @if ($booking['note'])
                                                    <p class="mt-2 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $booking['note'] }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="min-w-56 space-y-2 rounded-[1.2rem] bg-[rgba(244,237,218,0.72)] p-4">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Status</span>
                                                <span class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] {{ $booking['status'] === 'active' ? 'bg-[var(--tp-pine)] text-white' : 'border border-dashed border-[rgba(61,52,39,0.18)] bg-white/70 text-[rgba(61,52,39,0.72)]' }}">{{ ucfirst($booking['status']) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Amount</span>
                                                <span class="text-sm font-semibold text-[var(--tp-bark)]">${{ number_format(($booking['amount_cents'] ?? 0) / 100, 0) }}</span>
                                            </div>
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Payment</span>
                                                <span class="text-sm font-semibold text-[var(--tp-bark)]">{{ $paymentLabels[$booking['payment_status']] ?? ucfirst($booking['payment_status']) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($booking['can_update_payment'])
                                        <form method="POST" action="{{ route('bookings.payment.update', $booking['group']) }}" class="mt-5 grid gap-4 border-t border-[rgba(61,52,39,0.08)] pt-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                                            @csrf
                                            @method('PATCH')

                                            <div>
                                                <x-input-label for="payment_method_{{ $booking['group'] }}" :value="__('Payment Method')" />
                                                <select id="payment_method_{{ $booking['group'] }}" name="payment_method" class="mt-2 w-full rounded-2xl border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">
                                                    @foreach ($paymentMethods as $methodValue => $methodLabel)
                                                        <option value="{{ $methodValue }}" @selected(old('payment_method', $booking['payment_method']) === $methodValue)>{{ $methodLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <x-input-label for="payment_reference_{{ $booking['group'] }}" :value="__('Payment Reference')" />
                                                <x-text-input id="payment_reference_{{ $booking['group'] }}" name="payment_reference" type="text" class="mt-2 w-full" :value="old('payment_reference', $booking['payment_reference'])" placeholder="Transaction ID, note, or last 4 digits" />
                                            </div>

                                            <x-primary-button class="justify-center">{{ __('Update Payment') }}</x-primary-button>
                                        </form>
                                    @endif
                                </article>
                            @empty
                                <div class="rounded-[1.4rem] border border-dashed border-[rgba(61,52,39,0.18)] bg-white/50 px-5 py-8 text-center text-sm leading-6 text-[rgba(61,52,39,0.68)]">
                                    You have not submitted any stays yet. Use the booking form to reserve dates for one or more living areas.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </section>

                <aside class="space-y-6">
                    <section class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-6 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">Request a stay</p>
                        <h3 class="mt-2 font-display text-3xl text-[var(--tp-bark)]">Create a draft booking</h3>
                        <p class="mt-2 text-sm leading-6 text-[rgba(61,52,39,0.72)]">Choose one or more living areas, enter your guest name, and submit a draft. Drafts block the same dates until a poobah or admin reviews them.</p>

                        <form method="POST" action="{{ route('bookings.store') }}" class="mt-6 space-y-5">
                            @csrf

                            <fieldset>
                                <legend class="text-sm font-semibold uppercase tracking-[0.12em] text-[rgba(61,52,39,0.72)]">Living areas</legend>
                                <div class="mt-3 space-y-3">
                                    @foreach ($livingAreas as $area)
                                        <label class="flex items-start gap-3 rounded-[1.25rem] border border-[rgba(61,52,39,0.1)] bg-white/75 px-4 py-3 shadow-sm transition hover:border-[var(--tp-lake)]">
                                            <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" class="mt-1 h-4 w-4 rounded border-[rgba(61,52,39,0.2)] text-[var(--tp-lake)] focus:ring-[var(--tp-lake)]" @checked(collect(old('living_area_ids', []))->contains($area->id))>
                                            <span class="min-w-0 flex-1">
                                                <span class="flex items-center gap-2 font-semibold text-[var(--tp-bark)]">
                                                    <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                    {{ $area->name }}
                                                </span>
                                                <span class="mt-1 block text-sm leading-6 text-[rgba(61,52,39,0.68)]">{{ $area->description() }}</span>
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
                                <textarea id="note" name="note" rows="4" class="mt-2 w-full rounded-[1.5rem] border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]" placeholder="Optional details for the poobah or admin">{{ old('note') }}</textarea>
                            </div>

                            <div class="rounded-[1.4rem] bg-[rgba(244,237,218,0.72)] p-4 text-sm leading-6 text-[rgba(61,52,39,0.76)]">
                                <p class="font-semibold text-[var(--tp-bark)]">Pricing in this first release</p>
                                <ul class="mt-2 space-y-1">
                                    <li>$10 per night if you are poobah for the living area you are booking.</li>
                                    <li>$20 per night for standard bookings.</li>
                                    <li>$500 for booking all four living areas for a full 7-night week.</li>
                                </ul>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <x-input-label for="payment_method" :value="__('Payment Method')" />
                                    <select id="payment_method" name="payment_method" class="mt-2 w-full rounded-2xl border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">
                                        @foreach ($paymentMethods as $methodValue => $methodLabel)
                                            <option value="{{ $methodValue }}" @selected(old('payment_method', 'pay_later') === $methodValue)>{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <x-input-label for="payment_reference" :value="__('Payment Reference')" />
                                    <x-text-input id="payment_reference" name="payment_reference" type="text" class="mt-2 w-full" :value="old('payment_reference')" placeholder="Optional for now" />
                                </div>
                            </div>

                            <x-primary-button class="w-full justify-center">{{ __('Submit Draft Booking') }}</x-primary-button>
                        </form>
                    </section>

                    <section class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(34,67,45,0.96)] p-6 text-[var(--tp-paper)] shadow-[0_18px_60px_rgba(34,67,45,0.24)]">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[rgba(255,248,238,0.72)]">Current rules</p>
                        <ul class="mt-4 space-y-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">
                            <li>Draft and active bookings both block overlapping date requests.</li>
                            <li>Every booking request must include a guest name.</li>
                            <li>Payment details are visible on your draft bookings and can be updated later.</li>
                        </ul>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
