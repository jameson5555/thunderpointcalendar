<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Thunderpoint') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Caprasimo&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[var(--tp-ink)]">
        <div class="min-h-screen bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('images/thunderpoint-sunset.jpg') }}');">
            <main class="mx-auto flex min-h-screen max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
                <section class="grid w-full gap-6 lg:grid-cols-[minmax(22rem,28rem)_minmax(0,1fr)] lg:items-stretch">
                    <section class="home-surface tp-surface bg-[rgba(251,248,242,0.94)] p-5 sm:p-6 lg:p-7">
                        <div class="mb-6 flex justify-center lg:justify-start">
                            <a href="{{ route('home') }}" class="font-display text-[2.7rem] leading-none text-[var(--tp-bark)] sm:text-[3.2rem]">Thunderpoint</a>
                        </div>

                        {{ $slot }}
                    </section>

                    <x-thunderpoint-sign class="min-h-[18rem] sm:min-h-[24rem] lg:min-h-full" />
                </section>
            </main>
        </div>
    </body>
</html>
