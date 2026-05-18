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
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,rgba(244,180,108,0.16),transparent_32%),linear-gradient(180deg,#f7f1df_0%,#efe5cf_100%)]">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-[rgba(61,52,39,0.12)] bg-[rgba(255,250,240,0.8)] backdrop-blur">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="pb-12">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
