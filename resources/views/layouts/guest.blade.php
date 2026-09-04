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
        <link rel="preload" as="image" href="{{ asset('images/thunderpoint-sunset.webp') }}" type="image/webp" fetchpriority="high">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-[var(--tp-ink)]">
        <a href="#main-content" class="tp-skip-link">Skip to main content</a>
        <div class="min-h-screen bg-cover bg-center bg-no-repeat" style="background-image: image-set(url('{{ asset('images/thunderpoint-sunset.webp') }}') type('image/webp'), url('{{ asset('images/thunderpoint-sunset.jpg') }}') type('image/jpeg'));">
            <main id="main-content" tabindex="-1" class="mx-auto flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
                <section class="w-full max-w-[600px]">
                    <section class="tp-surface tp-surface--action p-5 sm:p-6 lg:p-7">
                        <div class="mb-6 flex justify-center">
                            <a href="{{ route('home') }}" class="font-display text-[2rem] leading-none text-[var(--tp-bark)] min-[400px]:text-[2.7rem] sm:text-[3.2rem]">Thunderpoint</a>
                        </div>

                        {{ $slot }}
                    </section>
                </section>
            </main>
        </div>

        <x-flash-toasts />
    </body>
</html>
