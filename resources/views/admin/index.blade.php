<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="tp-meta text-[var(--tp-brass)]">Admin</p>
            <h2 class="mt-2 font-display text-3xl text-[var(--tp-bark)]">{{ $isAdminView ? 'Thunderpoint administration' : 'Poobah area management' }}</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-[var(--tp-muted)]">{{ $isAdminView ? 'Approve people and bookings, manage areas, and assign roles.' : 'Review stays for your areas and update their settings.' }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @php
            $livingAreaColorMap = $livingAreas->mapWithKeys(fn ($area) => [$area->name => $area->deep_color]);
            $paymentLabels = [
                'unpaid' => 'Pay later',
                'pending' => 'Payment selected',
                'submitted' => 'Payment submitted',
            ];
        @endphp

        @if ($errors->any())
            <div class="mb-6 rounded-[1rem] border border-[rgba(221,79,22,0.22)] bg-[rgba(255,252,245,0.92)] px-5 py-4 text-sm text-[var(--tp-bark)]">
                <p class="font-semibold">There was a problem saving one of the admin forms.</p>
                <ul class="mt-2 space-y-1 leading-6 text-[var(--tp-muted)]">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2">
                    @forelse ($bookingGroups as $booking)
                        @php
                            $statusClass = match ($booking['status']) {
                                'active' => 'text-[var(--tp-pine)]',
                                'mixed' => 'text-[var(--tp-lake)]',
                                default => 'text-[var(--tp-muted)]',
                            };
                        @endphp
                        <article id="booking-group-{{ $booking['group'] }}" class="tp-surface p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($booking['areas'] as $areaName)
                                            <span class="tp-chip border-transparent text-white" style="background-color: {{ $livingAreaColorMap[$areaName] ?? 'var(--tp-brown)' }};">{{ $areaName }}</span>
                                        @endforeach
                                    </div>

                                    <h3 class="mt-4 font-display text-2xl text-[var(--tp-bark)]">{{ $booking['guest_name'] }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-[var(--tp-muted)]">Requested by {{ $booking['requested_by'] ?? 'Unknown user' }}</p>
                                    <p class="text-sm leading-6 text-[var(--tp-muted)]">{{ $booking['start_date']->format('M j, Y') }} to {{ $booking['end_date']->format('M j, Y') }}</p>
                                </div>

                                <span class="text-xs font-semibold uppercase tracking-[0.16em] {{ $statusClass }}">{{ ucfirst($booking['status']) }}</span>
                            </div>

                            <div class="mt-5 grid gap-3 rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,252,245,0.92)] p-4 text-sm text-[var(--tp-bark)] md:grid-cols-2">
                                <div>
                                    <p class="tp-meta">Amount</p>
                                    <p class="mt-1 font-semibold">${{ number_format(($booking['amount_cents'] ?? 0) / 100, 0) }}</p>
                                </div>
                                <div>
                                    <p class="tp-meta">Payment</p>
                                    <p class="mt-1 font-semibold">{{ $paymentLabels[$booking['payment_status']] ?? ucfirst($booking['payment_status']) }}</p>
                                </div>
                                <div>
                                    <p class="tp-meta">Method</p>
                                    <p class="mt-1 font-semibold">{{ $paymentMethods[$booking['payment_method']] ?? strtoupper(str_replace('_', ' ', (string) $booking['payment_method'])) }}</p>
                                </div>
                                <div>
                                    <p class="tp-meta">Reference</p>
                                    <p class="mt-1 font-semibold">{{ $booking['payment_reference'] ?: 'None yet' }}</p>
                                </div>
                            </div>

                            @if ($booking['note'])
                                <div class="mt-4 rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(253,251,247,0.76)] px-4 py-3 text-sm leading-6 text-[var(--tp-muted)]">
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
                                <details class="mt-5 rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(253,251,247,0.76)] px-4 py-4">
                                    <summary class="cursor-pointer text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-brass)]"><span class="ml-2">Edit or cancel this stay</span></summary>

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
                                                    <label class="flex items-center gap-3 rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.8)] px-4 py-3 text-sm font-semibold text-[var(--tp-bark)]">
                                                        <input type="checkbox" name="living_area_ids[]" value="{{ $area->id }}" class="rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] focus:ring-[var(--tp-focus)]" @checked(collect(old('living_area_ids', $booking['area_ids']->all()))->contains($area->id))>
                                                        <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                        <span>{{ $area->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label :for="'edit_note_'.$booking['group']" :value="__('Stay Note')" />
                                            <textarea id="{{ 'edit_note_'.$booking['group'] }}" name="note" rows="4" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">{{ old('note', $booking['note']) }}</textarea>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <x-input-label :for="'edit_payment_method_'.$booking['group']" :value="__('Payment Method')" />
                                                <select id="{{ 'edit_payment_method_'.$booking['group'] }}" name="payment_method" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">
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
                                        <button type="submit" class="inline-flex items-center justify-center rounded-full border border-[rgba(152,97,70,0.24)] bg-[rgba(152,97,70,0.1)] px-4 py-2 text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-ember)]">Cancel Stay</button>
                                    </form>
                                </details>
                            @else
                                <p class="mt-5 text-sm font-semibold {{ $booking['status'] === 'cancelled' ? 'text-[var(--tp-ember)]' : 'text-[var(--tp-pine)]' }}">
                                    {{ $booking['status'] === 'cancelled'
                                        ? 'Cancelled '.optional($booking['cancelled_at'])->format('M j, Y g:i a')
                                        : 'Approved '.optional($booking['approved_at'])->format('M j, Y g:i a') }}
                                </p>
                            @endif

                            <details class="mt-5 rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(253,251,247,0.76)] px-4 py-4">
                                <summary class="cursor-pointer text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-brass)]"><span class="ml-2">Booking history</span></summary>

                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div>
                                        <p class="tp-meta">Activity</p>
                                        <div class="mt-3 space-y-3">
                                            @forelse ($booking['history']['activity'] as $entry)
                                                <div class="rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(239,230,218,0.4)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                                    <p class="font-semibold">{{ $entry['headline'] }}</p>
                                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[var(--tp-muted)]">{{ $entry['context'] }}</p>
                                                </div>
                                            @empty
                                                <p class="text-sm leading-6 text-[var(--tp-muted)]">No activity yet.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div>
                                        <p class="tp-meta">Emails</p>
                                        <div class="mt-3 space-y-3">
                                            @forelse ($booking['history']['notifications'] as $entry)
                                                <div class="rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(239,230,218,0.4)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                                    <p class="font-semibold">{{ $entry['headline'] }}</p>
                                                    <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[var(--tp-muted)]">{{ $entry['context'] }}</p>
                                                </div>
                                            @empty
                                                <p class="text-sm leading-6 text-[var(--tp-muted)]">No emails yet.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </article>
                    @empty
                        <div class="rounded-[1rem] border border-dashed border-[var(--tp-border)] bg-[rgba(253,251,247,0.55)] px-5 py-8 text-center text-sm leading-6 text-[var(--tp-muted)] md:col-span-2">
                            No booking requests need attention right now.
                        </div>
                    @endforelse
                </div>

                <section class="grid gap-4 md:grid-cols-2">
                @foreach ($livingAreas as $area)
                    <article class="tp-surface p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-display text-2xl text-[var(--tp-bark)]">{{ $area->name }}</h3>
                            <span class="inline-flex h-4 w-4 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                        </div>
                        <form method="POST" action="{{ route('admin.living-areas.update', $area) }}" class="mt-5 space-y-4">
                            @csrf
                            @method('PATCH')

                            <div>
                                <x-input-label :for="'name_'.$area->id" :value="__('Area Name')" />
                                <x-text-input id="{{ 'name_'.$area->id }}" name="name" type="text" class="mt-2 w-full" :value="old('name', $area->name)" />
                            </div>

                            <div>
                                <x-input-label :for="'booking_message_'.$area->id" :value="__('Booking Form Message')" />
                                <textarea id="{{ 'booking_message_'.$area->id }}" name="booking_message" rows="4" class="mt-2 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">{{ old('booking_message', $area->booking_message) }}</textarea>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[rgba(61,52,39,0.7)]">
                                @foreach ($area->managers as $manager)
                                    <span class="tp-chip">Poobah: {{ $manager->name }}</span>
                                @endforeach
                                @if ($area->managers->isEmpty())
                                    <span class="tp-chip">No Poobah assigned yet</span>
                                @endif
                            </div>

                            <x-primary-button class="justify-center">{{ __('Save Area Settings') }}</x-primary-button>
                        </form>
                    </article>
                @endforeach
                </section>

                @if ($isAdminView)
                    <section class="tp-surface p-5">
                        <div>
                            <p class="tp-meta">User approvals</p>
                            <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">Pending account approvals</h3>
                        </div>

                        <div class="mt-6 space-y-4">
                            @forelse ($pendingUsers as $pendingUser)
                                <article class="tp-surface-subtle p-4">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <div class="flex flex-wrap gap-2">
                                                <span class="tp-chip border-transparent bg-[var(--tp-orange)] text-white">Pending</span>
                                            </div>
                                            <h4 class="mt-4 font-display text-2xl text-[var(--tp-bark)]">{{ $pendingUser->name }}</h4>
                                            <p class="mt-1 text-sm text-[var(--tp-muted)]">{{ $pendingUser->email }}</p>
                                            <p class="mt-2 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--tp-muted)]">Registered {{ optional($pendingUser->created_at)->format('M j, Y g:i a') }}</p>
                                        </div>

                                        <form method="POST" action="{{ route('admin.users.approve', $pendingUser) }}" class="sm:min-w-[12rem]">
                                            @csrf
                                            @method('PATCH')

                                            <x-primary-button class="w-full justify-center">{{ __('Approve User') }}</x-primary-button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-[1rem] border border-dashed border-[var(--tp-border)] bg-[rgba(253,251,247,0.55)] px-5 py-6 text-sm leading-6 text-[var(--tp-muted)]">
                                    No pending users right now.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="tp-surface p-5">
                        <div>
                            <p class="tp-meta">User roles</p>
                            <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">Assign Poobahs by living area</h3>
                        </div>

                        <div class="mt-6 space-y-5">
                            @foreach ($users as $managedUser)
                                <article class="tp-surface-subtle p-4">
                                    <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                                        <h4 class="font-display text-2xl text-[var(--tp-bark)]">{{ $managedUser->name }}</h4>
                                        <p class="text-sm text-[var(--tp-muted)]">{{ $managedUser->email }}</p>
                                    </div>

                                    <div class="mt-4 grid gap-3 lg:grid-cols-2">
                                        @foreach ($livingAreas as $area)
                                            <form method="POST" action="{{ route('admin.living-areas.managers.update', [$area, $managedUser]) }}" class="rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(239,230,218,0.4)] p-4">
                                                @csrf
                                                @method('PATCH')

                                                <div class="flex items-center justify-between gap-3">
                                                    <div>
                                                        <p class="font-semibold text-[var(--tp-bark)]">{{ $area->name }}</p>
                                                        <p class="tp-meta">Poobah access</p>
                                                    </div>
                                                    <span class="inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area->deep_color }};"></span>
                                                </div>

                                                <select name="role" class="mt-3 w-full rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(255,248,235,0.92)] px-4 py-3 text-[var(--tp-bark)] shadow-sm focus:border-[var(--tp-ember)] focus:ring-[var(--tp-focus)]">
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
                <section class="tp-surface-subtle p-6">
                    <p class="tp-meta">Live controls</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-[var(--tp-muted)]">
                        @if ($isAdminView)
                            <li>Approve new accounts before they can sign in and access the calendar.</li>
                        @endif
                        <li>Review every booking group with payment method and reference details.</li>
                        <li>Approve only the living areas you manage, or everything if you are site admin.</li>
                        <li>{{ $isAdminView ? 'Assign Poobah access, rename areas, and edit booking-form messages.' : 'Rename your living areas and tailor the booking message shown to users.' }}</li>
                    </ul>
                </section>

                <section class="tp-surface p-5">
                    <p class="tp-meta">Recent activity</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentActivity as $entry)
                            <a href="#booking-group-{{ $entry['booking_group'] }}" class="block rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(253,251,247,0.76)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                <p class="font-semibold">{{ $entry['headline'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[var(--tp-muted)]">{{ $entry['context'] }}</p>
                            </a>
                        @empty
                            <p class="text-sm leading-6 text-[var(--tp-muted)]">No recent activity.</p>
                        @endforelse
                    </div>
                </section>

                <section class="tp-surface p-5">
                    <p class="tp-meta">Recent emails</p>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentNotifications as $entry)
                            <a href="#booking-group-{{ $entry['booking_group'] }}" class="block rounded-[1rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(253,251,247,0.76)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                <p class="font-semibold">{{ $entry['headline'] }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.16em] text-[var(--tp-muted)]">{{ $entry['context'] }}</p>
                            </a>
                        @empty
                            <p class="text-sm leading-6 text-[var(--tp-muted)]">No recent emails.</p>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>