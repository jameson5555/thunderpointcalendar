<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--tp-pine)]">Admin</p>
            <h2 class="mt-2 font-display text-3xl text-[var(--tp-bark)]">Thunderpoint administration</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-[rgba(61,52,39,0.72)]">This is the first admin surface. Per-area approvals, custom booking messages, and user role assignment are the next pieces to wire in.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
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

            <aside class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(34,67,45,0.96)] p-6 text-[var(--tp-paper)] shadow-[0_18px_60px_rgba(34,67,45,0.24)]">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[rgba(255,248,238,0.72)]">Planned controls</p>
                <ul class="mt-4 space-y-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">
                    <li>Approve draft bookings and convert them to active stays.</li>
                    <li>Assign poobah rights by living area.</li>
                    <li>Review payment status before approving paid stays.</li>
                </ul>
            </aside>
        </div>
    </div>
</x-app-layout>