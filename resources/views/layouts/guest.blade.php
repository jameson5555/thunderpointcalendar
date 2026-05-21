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
            <div class="mx-auto grid w-full max-w-6xl gap-8 lg:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)] lg:items-center">
                <div class="space-y-6">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-[var(--tp-ink)]">
                        <x-application-logo class="w-auto" />
                    </a>
                    <div class="space-y-2">
                        <p class="tp-meta text-[var(--tp-lake)]">Thunderpoint Eastman</p>
                        <h1 class="max-w-lg font-display text-4xl leading-tight text-[var(--tp-bark)] sm:text-5xl">Private calendar access.</h1>
                    </div>
                    <x-thunderpoint-sign class="max-w-2xl" />
                </div>

                <div class="tp-surface w-full max-w-xl p-4 sm:p-6 lg:justify-self-end">
                    <div class="w-full overflow-hidden rounded-[1.25rem] border border-[rgba(47,37,29,0.08)] bg-[rgba(249,247,242,0.82)] px-6 py-5 sm:px-8 sm:py-6">
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
