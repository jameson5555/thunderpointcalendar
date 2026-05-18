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

        @if ($errors->any())
            <div class="mb-6 rounded-[1.4rem] border border-[rgba(159,87,42,0.18)] bg-[rgba(159,87,42,0.08)] px-5 py-4 text-sm text-[var(--tp-bark)]">
                <p class="font-semibold">There was a problem saving one of the admin forms.</p>
                <ul class="mt-2 space-y-1 leading-6 text-[rgba(61,52,39,0.78)]">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-6">
                @php
                    $selectedAreaIds = collect(old('living_area_ids', []))->map(fn ($id) => (int) $id);
                @endphp

                <section class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">Confirmed stays</p>
                        <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">Place a confirmed stay directly</h3>
                        <p class="mt-2 text-sm leading-6 text-[rgba(61,52,39,0.72)]">Use this when a manager is placing a stay that should be active immediately instead of waiting in the draft queue.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.bookings.active.store') }}" class="mt-6 space-y-5">
                        @csrf

                        <div>
                            <x-input-label for="active_guest_name" :value="__('Guest Name')" />
                            <x-text-input id="active_guest_name" name="guest_name" type="text" class="mt-2 w-full" :value="old('guest_name')" />
                        </div>

                        <div>
                            <x-input-label :value="__('Living Areas')" />
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                @foreach ($livingAreas as $area)
                                    <label class="flex items-center gap-3 rounded-[1rem] border border-[rgba(61,52,39,0.1)] bg-white/75 px-4 py-3 text-sm font-semibold text-[var(--tp-bark)]">
                                        <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" class="rounded border-[rgba(61,52,39,0.24)] text-[var(--tp-pine)] focus:ring-[var(--tp-lake)]" @checked($selectedAreaIds->contains($area->id))>
                                        <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                        <span>{{ $area->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="active_start_date" :value="__('Start Date')" />
                                <x-text-input id="active_start_date" name="start_date" type="date" class="mt-2 w-full" :value="old('start_date')" />
                            </div>
                            <div>
                                <x-input-label for="active_end_date" :value="__('End Date')" />
                                <x-text-input id="active_end_date" name="end_date" type="date" class="mt-2 w-full" :value="old('end_date')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="active_note" :value="__('Stay Note')" />
                            <textarea id="active_note" name="note" rows="4" class="mt-2 w-full rounded-[1.5rem] border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">{{ old('note') }}</textarea>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-input-label for="active_payment_method" :value="__('Payment Method')" />
                                <select id="active_payment_method" name="payment_method" class="mt-2 w-full rounded-[1.5rem] border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">
                                    @foreach ($paymentMethods as $paymentValue => $paymentLabel)
                                        <option value="{{ $paymentValue }}" @selected(old('payment_method', 'pay_later') === $paymentValue)>{{ $paymentLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="active_payment_reference" :value="__('Payment Reference')" />
                                <x-text-input id="active_payment_reference" name="payment_reference" type="text" class="mt-2 w-full" :value="old('payment_reference')" />
                            </div>
                        </div>

                        <x-primary-button class="justify-center">{{ __('Create Confirmed Stay') }}</x-primary-button>
                    </form>
                </section>

                <div class="grid gap-4 md:grid-cols-2">
                    @forelse ($bookingGroups as $booking)
                        @php
                            $statusClass = match ($booking['status']) {
                                'active' => 'bg-[var(--tp-pine)] text-white',
                                'mixed' => 'bg-[var(--tp-lake)] text-white',
                                default => 'border border-dashed border-[rgba(61,52,39,0.18)] bg-white/70 text-[rgba(61,52,39,0.72)]',
                            };
                        @endphp
                        <article id="booking-group-{{ $booking['group'] }}" class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
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

                            @if ($booking['note'])
                                <div class="mt-4 rounded-[1rem] border border-[rgba(61,52,39,0.08)] bg-white/75 px-4 py-3 text-sm leading-6 text-[rgba(61,52,39,0.76)]">
                                    {{ $booking['note'] }}
                                </div>
                            @endif

                            @if ($booking['status'] === 'draft')
                                <form method="POST" action="{{ route('admin.bookings.approve', $booking['group']) }}" class="mt-5">
                                    @csrf
                                    @method('PATCH')
                                    <x-primary-button class="justify-center">{{ __($isAdminView ? 'Approve Draft Booking' : 'Approve My Area Draft') }}</x-primary-button>
                                </form>
                            @elseif ($booking['status'] === 'active')
                                <details class="mt-5 rounded-[1.2rem] border border-[rgba(61,52,39,0.08)] bg-white/75 px-4 py-4">
                                    <summary class="cursor-pointer text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-pine)]">Edit or cancel this confirmed stay</summary>

                                    <form method="POST" action="{{ route('admin.bookings.update', $booking['group']) }}" class="mt-4 space-y-4">
                                        @csrf
                                        @method('PATCH')

                                        <div>
                                            <x-input-label :for="'edit_guest_name_'.$booking['group']" :value="__('Guest Name')" />
                                            <x-text-input id="{{ 'edit_guest_name_'.$booking['group'] }}" name="guest_name" type="text" class="mt-2 w-full" :value="old('guest_name', $booking['guest_name'])" />
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <x-input-label :for="'edit_start_date_'.$booking['group']" :value="__('Start Date')" />
                                                <x-text-input id="{{ 'edit_start_date_'.$booking['group'] }}" name="start_date" type="date" class="mt-2 w-full" :value="old('start_date', $booking['start_date']->toDateString())" />
                                            </div>
                                            <div>
                                                <x-input-label :for="'edit_end_date_'.$booking['group']" :value="__('End Date')" />
                                                <x-text-input id="{{ 'edit_end_date_'.$booking['group'] }}" name="end_date" type="date" class="mt-2 w-full" :value="old('end_date', $booking['end_date']->toDateString())" />
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Living Areas')" />
                                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                @foreach ($livingAreas as $area)
                                                    <label class="flex items-center gap-3 rounded-[1rem] border border-[rgba(61,52,39,0.1)] bg-white/75 px-4 py-3 text-sm font-semibold text-[var(--tp-bark)]">
                                                        <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" class="rounded border-[rgba(61,52,39,0.24)] text-[var(--tp-pine)] focus:ring-[var(--tp-lake)]" @checked(collect(old('living_area_ids', $booking['area_ids']->all()))->contains($area->id))>
                                                        <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                        <span>{{ $area->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label :for="'edit_note_'.$booking['group']" :value="__('Stay Note')" />
                                            <textarea id="{{ 'edit_note_'.$booking['group'] }}" name="note" rows="4" class="mt-2 w-full rounded-[1.5rem] border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">{{ old('note', $booking['note']) }}</textarea>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <x-input-label :for="'edit_payment_method_'.$booking['group']" :value="__('Payment Method')" />
                                                <select id="{{ 'edit_payment_method_'.$booking['group'] }}" name="payment_method" class="mt-2 w-full rounded-[1.5rem] border border-[rgba(61,52,39,0.14)] bg-white/90 px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-lake)] focus:ring-[var(--tp-lake)]">
                                                    @foreach ($paymentMethods as $paymentValue => $paymentLabel)
                                                        <option value="{{ $paymentValue }}" @selected(old('payment_method', $booking['payment_method']) === $paymentValue)>{{ $paymentLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <x-input-label :for="'edit_payment_reference_'.$booking['group']" :value="__('Payment Reference')" />
                                                <x-text-input id="{{ 'edit_payment_reference_'.$booking['group'] }}" name="payment_reference" type="text" class="mt-2 w-full" :value="old('payment_reference', $booking['payment_reference'])" />
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-3">
                                            <x-primary-button class="justify-center">{{ __('Save Confirmed Stay') }}</x-primary-button>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('admin.bookings.cancel', $booking['group']) }}" class="mt-4">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center justify-center rounded-full border border-[rgba(140,63,39,0.18)] bg-[rgba(140,63,39,0.08)] px-4 py-2 text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-ember)]">Cancel Confirmed Stay</button>
                                    </form>
                                </details>
                            @else
                                <p class="mt-5 text-sm font-semibold {{ $booking['status'] === 'cancelled' ? 'text-[var(--tp-ember)]' : 'text-[var(--tp-pine)]' }}">
                                    {{ $booking['status'] === 'cancelled'
                                        ? 'Cancelled '.optional($booking['cancelled_at'])->format('M j, Y g:i a')
                                        : 'Approved '.optional($booking['approved_at'])->format('M j, Y g:i a') }}
                                </p>
                            @endif

                            <details class="mt-5 rounded-[1.2rem] border border-[rgba(61,52,39,0.08)] bg-white/75 px-4 py-4">
                                <summary class="cursor-pointer text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-pine)]">Booking history</summary>

                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">Activity</p>
                                        <div class="mt-3 space-y-3">
                                            @forelse ($booking['history']['activity'] as $entry)
                                                <div class="rounded-[1rem] border border-[rgba(61,52,39,0.08)] bg-[rgba(244,237,218,0.56)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                                    <p class="font-semibold">{{ $entry['headline'] }}</p>
                                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">{{ $entry['context'] }}</p>
                                                </div>
                                            @empty
                                                <p class="text-sm leading-6 text-[rgba(61,52,39,0.68)]">No activity has been recorded for this booking group yet.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">Emails</p>
                                        <div class="mt-3 space-y-3">
                                            @forelse ($booking['history']['notifications'] as $entry)
                                                <div class="rounded-[1rem] border border-[rgba(61,52,39,0.08)] bg-[rgba(244,237,218,0.56)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                                    <p class="font-semibold">{{ $entry['headline'] }}</p>
                                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">{{ $entry['context'] }}</p>
                                                </div>
                                            @empty
                                                <p class="text-sm leading-6 text-[rgba(61,52,39,0.68)]">No emails have been logged for this booking group yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </details>
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

            <aside class="space-y-6">
                <section class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(34,67,45,0.96)] p-6 text-[var(--tp-paper)] shadow-[0_18px_60px_rgba(34,67,45,0.24)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[rgba(255,248,238,0.72)]">Live controls</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">
                        <li>Place confirmed stays directly when a manager is ready to skip the draft step.</li>
                        <li>Review every booking group with payment method and reference details.</li>
                        <li>Approve only the living areas you manage, or everything if you are site admin.</li>
                        <li>{{ $isAdminView ? 'Assign poobah access, rename areas, and edit booking-form messages.' : 'Rename your living areas and tailor the booking message shown to users.' }}</li>
                    </ul>
                </section>

                <section class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.9)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">Recent activity</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentActivity as $entry)
                            <a href="#booking-group-{{ $entry['booking_group'] }}" class="block rounded-[1rem] border border-[rgba(61,52,39,0.08)] bg-white/80 px-4 py-3 text-sm text-[var(--tp-bark)]">
                                <p class="font-semibold">{{ $entry['headline'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">{{ $entry['context'] }}</p>
                            </a>
                        @empty
                            <p class="text-sm leading-6 text-[rgba(61,52,39,0.68)]">No recent activity has been recorded yet for the living areas on this screen.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.9)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">Recent emails</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentNotifications as $entry)
                            <a href="#booking-group-{{ $entry['booking_group'] }}" class="block rounded-[1rem] border border-[rgba(61,52,39,0.08)] bg-white/80 px-4 py-3 text-sm text-[var(--tp-bark)]">
                                <p class="font-semibold">{{ $entry['headline'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[rgba(61,52,39,0.58)]">{{ $entry['context'] }}</p>
                            </a>
                        @empty
                            <p class="text-sm leading-6 text-[rgba(61,52,39,0.68)]">No recent emails have been logged yet for the living areas on this screen.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>