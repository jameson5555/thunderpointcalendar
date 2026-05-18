<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Thunderpoint') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=alegreya:500,700,800|mulish:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[var(--tp-paper)] font-sans antialiased text-[var(--tp-ink)]">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,rgba(49,91,63,0.12),transparent_30%),radial-gradient(circle_at_top_right,rgba(187,91,40,0.16),transparent_26%),linear-gradient(180deg,#f4edda_0%,#e7dcc2_100%)] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl space-y-5">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-[var(--tp-ink)]">
                        <x-application-logo class="w-auto" />
                    </a>
                    <div class="space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[var(--tp-pine)]">Shared camp calendar</p>
                        <h1 class="font-display text-4xl leading-tight text-[var(--tp-bark)] sm:text-5xl">Keep Thunderpoint easy to book, easy to trust, and easy to read.</h1>
                        <p class="max-w-lg text-base leading-7 text-[rgba(61,52,39,0.78)] sm:text-lg">Every stay runs through one friendly calendar for the Boathouse, Jack's Part, Jann's Part, and Joyce's Part. Accounts are approved before access so the family schedule stays private.</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-[var(--tp-bark)]">
                        <span class="tp-chip">Large-text, mobile-friendly layout</span>
                        <span class="tp-chip">Draft and active bookings</span>
                        <span class="tp-chip">Poobah and admin approval flow</span>
                    </div>
                </div>

                <div class="w-full max-w-xl rounded-[2rem] border border-[rgba(61,52,39,0.12)] bg-[rgba(255,250,240,0.84)] p-4 shadow-[0_24px_80px_rgba(61,52,39,0.12)] backdrop-blur sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3 rounded-[1.5rem] bg-[var(--tp-lake)] px-5 py-4 text-[var(--tp-paper)]">
                        <div>
                            <p class="text-xs uppercase tracking-[0.24em] text-[rgba(255,248,238,0.72)]">Members only</p>
                            <p class="mt-1 font-display text-2xl">Sign in to view the calendar</p>
                        </div>
                        <span class="hidden rounded-full border border-[rgba(255,255,255,0.28)] px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] sm:inline-flex">Approval required</span>
                    </div>

                    <div class="w-full overflow-hidden rounded-[1.5rem] bg-[rgba(255,255,255,0.8)] px-6 py-5 shadow-[inset_0_0_0_1px_rgba(61,52,39,0.08)] sm:px-8 sm:py-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <div class="mx-auto mt-8 max-w-6xl rounded-[1.75rem] border border-[rgba(61,52,39,0.1)] bg-[rgba(255,250,240,0.7)] p-5 shadow-[0_18px_60px_rgba(61,52,39,0.08)]">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[var(--tp-pine)]">Stay connected</p>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-[rgba(61,52,39,0.78)]">The calendar is the main event. Facebook stays secondary and outbound so booking stays fast and uncluttered.</p>
                    </div>
                    <a href="{{ config('thunderpoint.facebook_url') }}" target="_blank" rel="noreferrer" class="inline-flex items-center justify-center rounded-full border border-[rgba(61,52,39,0.16)] px-5 py-3 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Visit the Thunderpoint Facebook page</a>
                </div>
            </div>
        </div>
    </body>
</html>
