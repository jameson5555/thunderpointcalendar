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
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($livingAreas as $area)
                        <article class="rounded-[1.5rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.8)] p-5 shadow-[0_18px_50px_rgba(61,52,39,0.08)]">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[rgba(61,52,39,0.58)]">Living area</p>
                                    <h3 class="mt-2 font-display text-2xl text-[var(--tp-bark)]">{{ $area['name'] }}</h3>
                                </div>
                                <span class="inline-flex h-4 w-4 rounded-full border border-white/70 shadow-sm" style="background-color: {{ $area['deep_color'] }};"></span>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $area['description'] }}</p>
                            <div class="mt-5 flex gap-2 text-xs font-semibold uppercase tracking-[0.16em]">
                                <span class="rounded-full px-3 py-2" style="background-color: {{ $area['soft_color'] }}; color: {{ $area['deep_color'] }};">Active</span>
                                <span class="rounded-full border border-dashed border-[rgba(61,52,39,0.18)] bg-white/70 px-3 py-2 text-[rgba(61,52,39,0.68)]">Draft</span>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="overflow-hidden rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.84)] shadow-[0_24px_70px_rgba(61,52,39,0.08)]">
                    <div class="flex flex-col gap-3 border-b border-[rgba(61,52,39,0.08)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h3 class="font-display text-2xl text-[var(--tp-bark)]">Month view</h3>
                            <p class="mt-1 text-sm text-[rgba(61,52,39,0.68)]">Designed to stay readable on both desktop and mobile while keeping TeamUp-style familiarity.</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-[rgba(61,52,39,0.62)]">
                            <span class="tp-chip">Draft uses a bordered badge</span>
                            <span class="tp-chip">Active uses solid color</span>
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
                                        <div class="rounded-2xl px-3 py-2 text-xs font-semibold leading-5 {{ $marker['style'] }}">{{ $marker['label'] }}</div>
                                    @empty
                                        <p class="text-xs leading-5 text-[rgba(61,52,39,0.48)]">Open for booking</p>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <aside class="space-y-6">
                <section class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-6 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[var(--tp-pine)]">Booking rules</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-[rgba(61,52,39,0.78)]">
                        <li>Standard users can add draft dates where no active booking exists.</li>
                        <li>Only one draft hold per living area and date range will be allowed.</li>
                        <li>Poobahs and admins will promote approved drafts to active bookings.</li>
                    </ul>
                </section>

                <section class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(34,67,45,0.96)] p-6 text-[var(--tp-paper)] shadow-[0_18px_60px_rgba(34,67,45,0.24)]">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[rgba(255,248,238,0.72)]">Next in line</p>
                    <h3 class="mt-3 font-display text-3xl">Booking and approval flow</h3>
                    <p class="mt-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">The next implementation slice will wire this calendar to live bookings, per-area poobah rights, payment status, and email approvals.</p>
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
