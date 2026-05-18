<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--tp-pine)]">Admin</p>
            <h2 class="mt-2 font-display text-3xl text-[var(--tp-bark)]">{{ $isAdminView ? 'Thunderpoint administration' : 'Poobah area management' }}</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $isAdminView ? 'Approve bookings, manage living areas, and assign poobah access by area.' : 'Review bookings for your living areas, approve draft stays, and update your area settings.' }}</p>
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
                    @forelse ($bookingGroups as $booking)
                        @php
                            $statusClass = match ($booking['status']) {
                                'active' => 'bg-[var(--tp-pine)] text-white',
                                'mixed' => 'bg-[var(--tp-lake)] text-white',
                                default => 'border border-dashed border-[rgba(61,52,39,0.18)] bg-white/70 text-[rgba(61,52,39,0.72)]',
                            };
                        @endphp
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

                                <span class="rounded-full px-3 py-2 text-xs font-semibold uppercase tracking-[0.16em] {{ $statusClass }}">{{ ucfirst($booking['status']) }}</span>
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
                                    <x-primary-button class="justify-center">{{ __($isAdminView ? 'Approve Draft Booking' : 'Approve My Area Draft') }}</x-primary-button>
                                </form>
                            @else
                                <p class="mt-5 text-sm font-semibold text-[var(--tp-pine)]">Approved {{ optional($booking['approved_at'])->format('M j, Y g:i a') }}</p>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-[1.4rem] border border-dashed border-[rgba(61,52,39,0.18)] bg-white/50 px-5 py-8 text-center text-sm leading-6 text-[rgba(61,52,39,0.68)] md:col-span-2">
                            No booking requests currently need action for the living areas you manage.
                        </div>
                    @endforelse
                </div>

                <section class="grid gap-4 md:grid-cols-2">
                @foreach ($livingAreas as $area)
                    <article class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-display text-2xl text-[var(--tp-bark)]">{{ $area->name }}</h3>
                            <span class="inline-flex h-4 w-4 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $area->description() }}</p>

                        <form method="POST" action="{{ route('admin.living-areas.update', $area) }}" class="mt-5 space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <x-input-label :for="'name_'.$area->id" :value="__('Area Name')" />
                                <x-text-input id="{{ 'name_'.$area->id }}" name="name" type="text" class="mt-2 w-full" :value="old('name', $area->name)" />
                            </div>

                            <div>
                                <x-input-label :for="'booking_message_'.$area->id" :value="__('Booking Form Message')" />
                                <textarea id="{{ 'booking_message_'.$area->id }}" name="booking_message" rows="4" class="mt-2 w-full rounded-[1.5rem] border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">{{ old('booking_message', $area->booking_message) }}</textarea>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.7)]">
                                @foreach ($area->managers as $manager)
                                    <span class="tp-chip">Poobah: {{ $manager->name }}</span>
                                @endforeach
                                @if ($area->managers->isEmpty())
                                    <span class="tp-chip">No poobah assigned yet</span>
                                @endif
                            </div>

                            <x-primary-button class="justify-center">{{ __('Save Area Settings') }}</x-primary-button>
                        </form>
                    </article>
                @endforeach
                </section>

                @if ($isAdminView)
                    <section class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">User roles</p>
                            <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">Assign poobahs by living area</h3>
                        </div>

                        <div class="mt-6 space-y-5">
                            @foreach ($users as $managedUser)
                                <article class="rounded-[1.25rem] border border-[rgba(61,52,39,0.1)] bg-white/75 p-4 shadow-[0_10px_30px_rgba(61,52,39,0.05)]">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                                        <h4 class="font-display text-2xl text-[var(--tp-bark)]">{{ $managedUser->name }}</h4>
                                        <p class="text-sm text-[rgba(61,52,39,0.68)]">{{ $managedUser->email }}</p>
                                    </div>

                                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                        @foreach ($livingAreas as $area)
                                            <form method="POST" action="{{ route('admin.living-areas.managers.update', [$area, $managedUser]) }}" class="rounded-[1rem] border border-[rgba(61,52,39,0.08)] bg-[rgba(244,237,218,0.72)] p-4">
                                                @csrf
                                                @method('PATCH')

                                                <div class="flex items-center justify-between gap-3">
                                                    <div>
                                                        <p class="font-semibold text-[var(--tp-bark)]">{{ $area->name }}</p>
                                                        <p class="text-xs uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">Poobah access</p>
                                                    </div>
                                                    <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                </div>

                                                <select name="role" class="mt-3 w-full rounded-2xl border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">
                                                    <option value="standard" @selected(! $managedUser->managedAreas->contains('id', $area->id))>Standard</option>
                                                    <option value="poobah" @selected($managedUser->managedAreas->contains('id', $area->id))>Poobah</option>
                                                </select>

                                                <x-primary-button class="mt-3 justify-center">{{ __('Save Role') }}</x-primary-button>
                                            </form>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif
            </section>

            <aside class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(34,67,45,0.96)] p-6 text-[var(--tp-paper)] shadow-[0_18px_60px_rgba(34,67,45,0.24)]">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[rgba(255,248,238,0.72)]">Live controls</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">
                    <li>Review every draft booking group with payment method and reference details.</li>
                    <li>Approve only the living areas you manage, or everything if you are site admin.</li>
                    <li>{{ $isAdminView ? 'Assign poobah access, rename areas, and edit booking-form messages.' : 'Rename your living areas and tailor the booking message shown to users.' }}</li>
                </ul>
            </aside>
        </div>
    </div>
</x-app-layout>