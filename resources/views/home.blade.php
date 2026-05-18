<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Thunderpoint') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=alegreya:500,700,800|mulish:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[var(--tp-paper)] font-sans text-[var(--tp-ink)] antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(49,91,63,0.14),transparent_30%),radial-gradient(circle_at_top_right,rgba(198,106,43,0.16),transparent_28%),linear-gradient(180deg,#f5efdc_0%,#eadfc7_100%)]">
            <header class="border-b border-[rgba(61,52,39,0.12)] bg-[rgba(255,250,240,0.76)] backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                        <x-application-logo class="w-auto" />
                    </a>

                    <nav class="flex items-center gap-2 sm:gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-full border border-[rgba(61,52,39,0.14)] bg-white/80 px-4 py-3 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Open calendar</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.index') }}" class="rounded-full border border-[rgba(61,52,39,0.14)] px-4 py-3 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-full bg-[var(--tp-lake)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--tp-pine)]">Sign out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-[rgba(61,52,39,0.14)] px-4 py-3 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Sign in</a>
                            <a href="{{ route('register') }}" class="rounded-full bg-[var(--tp-lake)] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[var(--tp-pine)]">Create account</a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
                <section class="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                    <div class="space-y-6">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-[var(--tp-pine)]">Northwoods summer camp vibe</p>
                        <h1 class="max-w-3xl font-display text-5xl leading-[0.95] text-[var(--tp-bark)] sm:text-6xl xl:text-7xl">The shared Thunderpoint calendar, rebuilt for family booking.</h1>
                        <p class="max-w-2xl text-lg leading-8 text-[rgba(61,52,39,0.78)]">Thunderpoint keeps the Boathouse, Jack's Part, Jann's Part, and Joyce's Part in one friendly place. Registration is required, every account is approved before access, and the calendar is designed to stay legible for older family members on both phones and desktops.</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="rounded-full bg-[var(--tp-ember)] px-6 py-4 text-sm font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-[var(--tp-pine)]">Request access</a>
                            <a href="{{ route('login') }}" class="rounded-full border border-[rgba(61,52,39,0.16)] bg-white/70 px-6 py-4 text-sm font-semibold uppercase tracking-[0.14em] text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Member sign in</a>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.8)] p-5 shadow-[0_24px_80px_rgba(61,52,39,0.1)]">
                        <div class="rounded-[1.5rem] bg-[linear-gradient(135deg,rgba(34,67,45,0.96),rgba(47,111,135,0.92))] p-6 text-[var(--tp-paper)]">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[rgba(255,248,238,0.72)]">Coming into view</p>
                            <h2 class="mt-3 font-display text-3xl">A calendar that feels familiar without feeling old.</h2>
                            <p class="mt-3 text-sm leading-6 text-[rgba(255,248,238,0.84)]">Color-coded living areas, draft versus active stays, payment tracking, and poobah/admin approvals are all being built into this first release.</p>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            @foreach (config('thunderpoint.living_areas') as $area)
                                <article class="rounded-[1.35rem] border border-[rgba(61,52,39,0.08)] bg-white/75 p-4 shadow-[0_10px_30px_rgba(61,52,39,0.05)]">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="font-display text-2xl text-[var(--tp-bark)]">{{ $area['name'] }}</h3>
                                        <span class="inline-flex h-4 w-4 rounded-full" style="background-color: {{ $area['deep_color'] }};"></span>
                                    </div>
                                    <p class="mt-2 text-sm leading-6 text-[rgba(61,52,39,0.72)]">{{ $area['description'] }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="mt-10 grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                    <article class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-6 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[var(--tp-pine)]">How it works</p>
                        <ul class="mt-5 space-y-4 text-base leading-7 text-[rgba(61,52,39,0.78)]">
                            <li>Create an account, then wait for approval before the calendar opens.</li>
                            <li>View all bookings in one shared calendar, with draft and active status clearly separated.</li>
                            <li>Draft your stay request with a required guest name, optional note, and payment details tracked for approval.</li>
                        </ul>
                    </article>

                    <article class="rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.82)] p-6 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[var(--tp-pine)]">Connected but uncluttered</p>
                                <p class="mt-2 text-base leading-7 text-[rgba(61,52,39,0.78)]">The Facebook page stays available as a secondary link while the main experience remains focused on booking.</p>
                            </div>
                            <a href="{{ config('thunderpoint.facebook_url') }}" target="_blank" rel="noreferrer" class="rounded-full border border-[rgba(61,52,39,0.16)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Open Facebook</a>
                        </div>
                    </article>
                </section>
            </main>
        </div>
    </body>
</html>