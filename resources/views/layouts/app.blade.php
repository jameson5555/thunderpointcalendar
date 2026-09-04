<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title }} · {{ config('app.name', 'Thunderpoint') }}</title>

        <x-favicon />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Caprasimo&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[var(--tp-paper)] font-sans antialiased text-[var(--tp-ink)]">
        <a href="#main-content" class="tp-skip-link">Skip to main content</a>
        <div id="app-shell" class="min-h-screen">
            @include('layouts.navigation')

            @isset($header)
                <header class="relative z-10 border-b border-[var(--tp-border)] bg-[var(--tp-shell)]">
                    <div class="mx-auto max-w-7xl px-3 py-2.5 sm:px-6 sm:py-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main id="main-content" tabindex="-1" class="pb-8 pt-0 sm:pb-12 sm:pt-2">
                {{ $slot }}
            </main>
        </div>

        <x-flash-toasts />
    </body>
</html>
