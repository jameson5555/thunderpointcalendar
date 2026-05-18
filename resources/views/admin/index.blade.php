<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--tp-pine)]">Admin</p>
            <h2 class="mt-2 font-display text-3xl text-[var(--tp-bark)]">Thunderpoint administration</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-[rgba(61,52,39,0.72)]">This is the first admin surface. Per-area approvals, custom booking messages, and user role assignment are the next pieces to wire in.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @php
            $paymentLabels = [
                'unpaid' => 'Pay later',
                'pending' => 'Payment selected',
                'submitted' => 'Payment submitted',
            ];
        @endphp

        @if (session('status'))
            <div class="mb-6 rounded-[1.4rem] border border-[rgba(49,91,63,0.16)] bg-[rgba(49,91,63,0.08)] px-5 py-4 text-sm font-semibold text-[var(--tp-pine)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($bookingGroups as $booking)
                        <article class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($booking['areas'] as $areaName)
                                            <span class="tp-chip">{{ $areaName }}</span>
                                        @endforeach
                                    </div>

                                    <h3 class="mt-4 font-display text-2xl text-[var(--tp-bark)]">{{ $booking['guest_name'] }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-[rgba(61,52,39,0.72)]">Requested by {{ $booking['requested_by'] ?? 'Unknown user' }}</p>
                                    <p class="text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $booking['start_date']->format('M j, Y') }} to {{ $booking['end_date']->format('M j, Y') }}</p>
                                </div>

                                <span class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] {{ $booking['status'] === 'active' ? 'bg-[var(--tp-pine)] text-white' : 'border border-dashed border-[rgba(61,52,39,0.18)] bg-white/70 text-[rgba(61,52,39,0.72)]' }}">{{ ucfirst($booking['status']) }}</span>
                            </div>

                            <div class="mt-5 grid gap-3 rounded-[1.25rem] bg-[rgba(244,237,218,0.72)] p-4 text-sm text-[var(--tp-bark)] md:grid-cols-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Amount</p>
                                    <p class="mt-1 font-semibold">${{ number_format(($booking['amount_cents'] ?? 0) / 100, 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Payment</p>
                                    <p class="mt-1 font-semibold">{{ $paymentLabels[$booking['payment_status']] ?? ucfirst($booking['payment_status']) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Method</p>
                                    <p class="mt-1 font-semibold">{{ $paymentMethods[$booking['payment_method']] ?? strtoupper(str_replace('_', ' ', (string) $booking['payment_method'])) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.62)]">Reference</p>
                                    <p class="mt-1 font-semibold">{{ $booking['payment_reference'] ?: 'None yet' }}</p>
                                </div>
                            </div>

                            @if ($booking['status'] === 'draft')
                                <form method="POST" action="{{ route('admin.bookings.approve', $booking['group']) }}" class="mt-5">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button class="justify-center">{{ __('Approve Draft Booking') }}</x-primary-button>
                                </form>
                            @else
                                <p class="mt-5 text-sm font-semibold text-[var(--tp-pine)]">Approved {{ optional($booking['approved_at'])->format('M j, Y g:i a') }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>

                <section class="grid gap-4 md:grid-cols-2">
                @foreach ($livingAreas as $area)
                    <article class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-display text-2xl text-[var(--tp-bark)]">{{ $area['name'] }}</h3>
                            <span class="inline-flex h-4 w-4 rounded-full" style="background-color: {{ $area['deep_color'] }};"></span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $area['description'] }}</p>
                        <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.7)]">
                            <span class="tp-chip">Rename area</span>
                            <span class="tp-chip">Review drafts</span>
                            <span class="tp-chip">Edit message</span>
                        </div>
                    </article>
                @endforeach
                </section>
            </section>

            <aside class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(34,67,45,0.96)] p-6 text-[var(--tp-paper)] shadow-[0_18px_60px_rgba(34,67,45,0.24)]">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[rgba(255,248,238,0.72)]">Live controls</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">
                    <li>Review every draft booking group with payment method and reference details.</li>
                    <li>Approve a full booking group into active status with one action.</li>
                    <li>Per-area poobah assignments and user-role management are next.</li>
                </ul>
            </aside>
        </div>
    </div>
</x-app-layout>