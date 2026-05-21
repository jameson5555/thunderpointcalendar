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
        <div class="min-h-screen">
            <header class="border-b border-[var(--tp-border)] bg-[rgba(250,247,241,0.78)] backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                        <x-application-logo class="w-auto" />
                    </a>

                    <nav class="flex items-center gap-2 sm:gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="tp-button-secondary">Open calendar</a>
                            @if (auth()->user()->isAdmin())
                                <a href="{{ route('admin.index') }}" class="tp-button-ghost">Admin</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="tp-button-ghost">Sign out</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="tp-button-ghost">Sign in</a>
                            <a href="{{ route('register') }}" class="tp-button-secondary">Request access</a>
                        @endauth
                    </nav>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-16">
                <section class="grid gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.85fr)] lg:items-end">
                    <div class="space-y-6">
                        <p class="tp-meta text-[var(--tp-brass)]">Private family calendar</p>
                        <h1 class="max-w-3xl font-display text-5xl leading-[0.94] text-[var(--tp-bark)] sm:text-6xl xl:text-7xl">A quiet place to schedule time at Thunderpoint.</h1>
                        <p class="max-w-2xl text-lg leading-8 text-[var(--tp-muted)]">One shared calendar for the Boathouse, Jack's Part, Jann's Part, and Joyce's Part. Request access, sign in, and keep family dates in one place.</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-[var(--tp-bark)] px-6 py-4 text-sm font-semibold uppercase tracking-[0.16em] text-[var(--tp-paper-soft)] transition hover:bg-[rgba(75,53,40,0.92)]">Request access</a>
                            <a href="{{ route('login') }}" class="tp-button-secondary px-6 py-4 uppercase tracking-[0.16em]">Member sign in</a>
                        </div>
                    </div>

                    <section class="tp-surface p-6 sm:p-7">
                        <div class="flex items-center justify-between gap-3 border-b border-[var(--tp-border)] pb-4">
                            <div>
                                <p class="tp-meta">Access</p>
                                <p class="mt-2 text-base leading-7 text-[var(--tp-muted)]">Accounts are approved before the calendar becomes visible.</p>
                            </div>
                            <span class="tp-chip border-transparent bg-[rgba(167,130,61,0.12)] text-[var(--tp-brass)]">Members only</span>
                        </div>

                        <div class="mt-5 grid gap-3">
                            @foreach (config('thunderpoint.living_areas') as $area)
                                <article class="tp-surface-subtle flex items-start justify-between gap-4 p-4">
                                    <div>
                                        <h2 class="font-display text-2xl text-[var(--tp-bark)]">{{ $area['name'] }}</h2>
                                        <p class="mt-1 text-sm leading-6 text-[var(--tp-muted)]">{{ $area['description'] }}</p>
                                    </div>
                                    <span class="mt-2 inline-flex h-3.5 w-3.5 rounded-full" style="background-color: {{ $area['deep_color'] }};"></span>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </section>

                <section class="mt-10 flex flex-col gap-4 border-t border-[var(--tp-border)] pt-6 text-sm text-[var(--tp-muted)] sm:flex-row sm:items-center sm:justify-between">
                    <p>For booking, sign in here. For everything else, the Facebook page stays separate.</p>
                    <a href="{{ config('thunderpoint.facebook_url') }}" target="_blank" rel="noreferrer" class="tp-link w-fit">Open Facebook</a>
                </section>
            </main>
        </div>
    </body>
</html>