<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Thunderpoint') }}</title>

        <x-favicon />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Caprasimo&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[var(--tp-ink)] antialiased">
        @php
            $selectedPanel = old('auth_panel', $authPanel);

            if (! in_array($selectedPanel, ['login', 'register'], true)) {
                $selectedPanel = $errors->getBag('register')->any() ? 'register' : 'login';
            }
        @endphp

        <div class="min-h-screen bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('images/thunderpoint-sunset.jpg') }}');">
            <main class="mx-auto flex min-h-screen max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
                <section class="grid w-full gap-6 lg:grid-cols-[minmax(22rem,28rem)_minmax(0,1fr)] lg:items-stretch">
                    <section id="access" x-data="{ panel: '{{ $selectedPanel }}' }" class="home-surface tp-surface bg-[rgba(251,248,242,0.94)] p-5 sm:p-6 lg:p-7">
                        <div class="mb-6 flex justify-center lg:justify-start">
                            <div class="font-display text-[2.7rem] leading-none text-[var(--tp-bark)] sm:text-[3.2rem]">Thunderpoint</div>
                        </div>

                        @auth
                            <div class="space-y-5">
                                <div class="space-y-2">
                                    <p class="tp-meta text-[var(--tp-lake)]">Signed in</p>
                                    <h2 class="font-display text-3xl text-[var(--tp-bark)]">{{ auth()->user()->name }}</h2>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                    <a href="{{ route('dashboard') }}" class="tp-button-primary">Open calendar</a>
                                    @if (auth()->user()->isAdmin())
                                        <a href="{{ route('admin.index') }}" class="tp-button-secondary">Admin</a>
                                    @endif
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="tp-button-secondary w-full sm:w-auto">Sign out</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-2 gap-2 rounded-full bg-[rgba(226,208,181,0.4)] p-1">
                                <button type="button" @click="panel = 'login'" :class="panel === 'login' ? 'bg-[rgba(255,252,247,0.96)] text-[var(--tp-bark)] shadow-sm' : 'text-[var(--tp-muted)]'" class="rounded-full px-4 py-3 text-sm font-semibold uppercase tracking-[0.16em] transition">Sign in</button>
                                <button type="button" @click="panel = 'register'" :class="panel === 'register' ? 'bg-[rgba(255,252,247,0.96)] text-[var(--tp-bark)] shadow-sm' : 'text-[var(--tp-muted)]'" class="rounded-full px-4 py-3 text-sm font-semibold uppercase tracking-[0.16em] transition">Register</button>
                            </div>

                            <div x-show="panel === 'login'" x-cloak class="mt-6">
                                <div class="mb-6">
                                    <h2 class="font-display text-3xl text-[var(--tp-bark)]">Sign in</h2>
                                </div>

                                @if ($errors->getBag('login')->any())
                                    <div class="mb-4 rounded-[1rem] border border-[rgba(122,74,86,0.18)] bg-[rgba(122,74,86,0.08)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                        <ul class="space-y-1">
                                            @foreach ($errors->getBag('login')->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="auth_panel" value="login">

                                    <div>
                                        <x-input-label for="home_login_email" :value="__('Email')" />
                                        <x-text-input id="home_login_email" class="mt-2 w-full" type="email" name="email" :value="old('auth_panel') === 'login' ? old('email') : ''" required autofocus autocomplete="username" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_login_password" :value="__('Password')" />
                                        <x-text-input id="home_login_password" class="mt-2 w-full" type="password" name="password" required autocomplete="current-password" />
                                    </div>

                                    <label for="home_remember_me" class="inline-flex items-center gap-2 text-sm text-[var(--tp-muted)]">
                                        <input id="home_remember_me" type="checkbox" class="rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] shadow-sm focus:ring-[var(--tp-focus)]" name="remember">
                                        <span>{{ __('Remember me') }}</span>
                                    </label>

                                    <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                                        @if (Route::has('password.request'))
                                            <a class="tp-link text-sm focus:outline-none" href="{{ route('password.request') }}">
                                                {{ __('Forgot your password?') }}
                                            </a>
                                        @endif

                                        <x-primary-button class="justify-center sm:ms-3">
                                            {{ __('Log in') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>

                            <div x-show="panel === 'register'" x-cloak class="mt-6">
                                <div class="mb-6">
                                    <h2 class="font-display text-3xl text-[var(--tp-bark)]">Register</h2>
                                </div>

                                @if ($errors->getBag('register')->any())
                                    <div class="mb-4 rounded-[1rem] border border-[rgba(122,74,86,0.18)] bg-[rgba(122,74,86,0.08)] px-4 py-3 text-sm text-[var(--tp-bark)]">
                                        <ul class="space-y-1">
                                            @foreach ($errors->getBag('register')->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="auth_panel" value="register">

                                    <div>
                                        <x-input-label for="home_register_name" :value="__('Name')" />
                                        <x-text-input id="home_register_name" class="mt-2 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_register_email" :value="__('Email')" />
                                        <x-text-input id="home_register_email" class="mt-2 w-full" type="email" name="email" :value="old('auth_panel') === 'register' ? old('email') : ''" required autocomplete="username" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_register_password" :value="__('Password')" />
                                        <x-text-input id="home_register_password" class="mt-2 w-full" type="password" name="password" required autocomplete="new-password" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_register_password_confirmation" :value="__('Confirm Password')" />
                                        <x-text-input id="home_register_password_confirmation" class="mt-2 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                                    </div>

                                    <x-primary-button class="w-full justify-center">
                                        {{ __('Register') }}
                                    </x-primary-button>
                                </form>
                            </div>
                        @endauth
                    </section>

                    <x-thunderpoint-sign class="min-h-[18rem] sm:min-h-[24rem] lg:min-h-full" />
                </section>
            </main>
        </div>

        <x-flash-toasts />
    </body>
</html>
