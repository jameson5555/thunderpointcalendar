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
        <div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-xl space-y-5">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-[var(--tp-ink)]">
                        <x-application-logo class="w-auto" />
                    </a>
                    <div class="space-y-3">
                        <p class="tp-meta text-[var(--tp-brass)]">Members only</p>
                        <h1 class="font-display text-4xl leading-tight text-[var(--tp-bark)] sm:text-5xl">Sign in to the Thunderpoint calendar.</h1>
                        <p class="max-w-lg text-base leading-7 text-[var(--tp-muted)] sm:text-lg">Private booking access for family stays across all four living areas.</p>
                    </div>
                </div>

                <div class="tp-surface w-full max-w-xl p-4 sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3 rounded-[1.25rem] border border-[var(--tp-border)] bg-[rgba(239,230,218,0.6)] px-5 py-4 text-[var(--tp-bark)]">
                        <div>
                            <p class="tp-meta text-[var(--tp-brass)]">Access</p>
                            <p class="mt-1 font-display text-2xl">Approval required</p>
                        </div>
                        <span class="hidden tp-chip sm:inline-flex">Private</span>
                    </div>

                    <div class="w-full overflow-hidden rounded-[1.25rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(253,251,247,0.78)] px-6 py-5 sm:px-8 sm:py-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <div class="mx-auto mt-8 flex max-w-6xl flex-col gap-3 border-t border-[var(--tp-border)] pt-5 text-sm text-[var(--tp-muted)] sm:flex-row sm:items-center sm:justify-between">
                <p>Facebook remains available separately.</p>
                <a href="{{ config('thunderpoint.facebook_url') }}" target="_blank" rel="noreferrer" class="tp-link w-fit">Visit Facebook</a>
            </div>
                </div>
            </div>
        </div>
    </body>
</html>
