<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Sign in or register · {{ config('app.name', 'Thunderpoint') }}</title>

        <x-favicon />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Caprasimo&family=Mulish:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="preload" as="image" href="{{ asset('images/thunderpoint-sunset.webp') }}" type="image/webp" fetchpriority="high">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-[var(--tp-ink)] antialiased">
        <a href="#main-content" class="tp-skip-link">Skip to main content</a>
        <x-environment-banner />
        @php
            $selectedPanel = old('auth_panel', $authPanel);

            if (! in_array($selectedPanel, ['login', 'register'], true)) {
                $selectedPanel = $errors->getBag('register')->any() ? 'register' : 'login';
            }
        @endphp

        <div class="min-h-screen bg-cover bg-center bg-no-repeat" style="background-image: image-set(url('{{ asset('images/thunderpoint-sunset.webp') }}') type('image/webp'), url('{{ asset('images/thunderpoint-sunset.jpg') }}') type('image/jpeg'));">
            <main id="main-content" tabindex="-1" class="mx-auto flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
                <section class="w-full max-w-[600px]">
                    <section
                        id="access"
                        x-data="{
                            panel: '{{ $selectedPanel }}',
                            selectPanel(next) {
                                this.panel = next;
                                this.$nextTick(() => this.$refs[`${next}Tab`]?.focus());
                            },
                        }"
                        class="tp-surface tp-surface--action p-5 sm:p-6 lg:p-7"
                    >
                        <div class="mb-6 flex justify-center">
                            <div class="font-display text-[2rem] leading-none text-[var(--tp-bark)] min-[400px]:text-[2.7rem] sm:text-[3.2rem]">Thunderpoint</div>
                        </div>

                        @auth
                            <div class="space-y-5">
                                <div class="space-y-2">
                                    <p class="tp-meta text-[var(--tp-status)]">Signed in</p>
                                    <h1 class="tp-heading-page">{{ auth()->user()->name }}</h1>
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
                            <h1 class="sr-only">Sign in or register</h1>
                            <div class="grid grid-cols-2 gap-2 rounded-full bg-[var(--tp-surface-muted)] p-1" role="tablist" aria-label="Account access">
                                <button id="login-tab" x-ref="loginTab" type="button" role="tab" aria-controls="login-panel" :aria-selected="(panel === 'login').toString()" :tabindex="panel === 'login' ? 0 : -1" @click="panel = 'login'" @keydown.right.prevent="selectPanel('register')" @keydown.end.prevent="selectPanel('register')" :class="panel === 'login' ? 'bg-[var(--tp-surface-raised)] text-[var(--tp-bark)] shadow-sm' : 'text-[var(--tp-muted)]'" class="rounded-full px-4 py-3 text-sm font-semibold uppercase tracking-[0.16em] transition">Sign in</button>
                                <button id="register-tab" x-ref="registerTab" type="button" role="tab" aria-controls="register-panel" :aria-selected="(panel === 'register').toString()" :tabindex="panel === 'register' ? 0 : -1" @click="panel = 'register'" @keydown.left.prevent="selectPanel('login')" @keydown.home.prevent="selectPanel('login')" :class="panel === 'register' ? 'bg-[var(--tp-surface-raised)] text-[var(--tp-bark)] shadow-sm' : 'text-[var(--tp-muted)]'" class="rounded-full px-4 py-3 text-sm font-semibold uppercase tracking-[0.16em] transition">Register</button>
                            </div>

                            <div id="login-panel" role="tabpanel" aria-labelledby="login-tab" x-show="panel === 'login'" x-cloak class="mt-6">
                                <div class="mb-6">
                                    <h2 class="tp-heading-section">Sign in</h2>
                                </div>

                                @if ($errors->getBag('login')->any())
                                    <div id="home-login-errors" class="mb-4 rounded-[1rem] border border-[var(--tp-error)] bg-[rgba(145,60,25,0.08)] px-4 py-3 text-sm text-[var(--tp-bark)]" role="alert" tabindex="-1" data-error-summary>
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
                                        <x-text-input id="home_login_email" class="mt-2 w-full" type="email" name="email" :value="old('auth_panel') === 'login' ? old('email') : ''" required autofocus autocomplete="username" :invalid="$errors->getBag('login')->has('email')" error-id="home-login-errors" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_login_password" :value="__('Password')" />
                                        <x-text-input id="home_login_password" class="mt-2 w-full" type="password" name="password" required autocomplete="current-password" :invalid="$errors->getBag('login')->has('password') || $errors->getBag('login')->has('email')" error-id="home-login-errors" />
                                    </div>

                                    <label for="home_remember_me" class="inline-flex items-center gap-2 text-sm text-[var(--tp-muted)]">
                                        <input id="home_remember_me" type="checkbox" class="rounded border-[var(--tp-border-strong)] text-[var(--tp-accent)] shadow-sm focus:ring-[var(--tp-focus)]" name="remember">
                                        <span>{{ __('Remember me') }}</span>
                                    </label>

                                    <div class="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
                                        @if (Route::has('password.request'))
                                            <a class="tp-link text-sm" href="{{ route('password.request') }}">
                                                {{ __('Forgot your password?') }}
                                            </a>
                                        @endif

                                        <x-primary-button class="justify-center sm:ms-3">
                                            {{ __('Log in') }}
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>

                            <div id="register-panel" role="tabpanel" aria-labelledby="register-tab" x-show="panel === 'register'" x-cloak class="mt-6">
                                <div class="mb-6">
                                    <h2 class="tp-heading-section">Register</h2>
                                </div>

                                @if ($errors->getBag('register')->any())
                                    <div id="home-register-errors" class="mb-4 rounded-[1rem] border border-[var(--tp-error)] bg-[rgba(145,60,25,0.08)] px-4 py-3 text-sm text-[var(--tp-bark)]" role="alert" tabindex="-1" data-error-summary>
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
                                        <x-text-input id="home_register_name" class="mt-2 w-full" type="text" name="name" :value="old('name')" required autocomplete="name" :invalid="$errors->getBag('register')->has('name')" error-id="home-register-errors" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_register_email" :value="__('Email')" />
                                        <x-text-input id="home_register_email" class="mt-2 w-full" type="email" name="email" :value="old('auth_panel') === 'register' ? old('email') : ''" required autocomplete="username" :invalid="$errors->getBag('register')->has('email')" error-id="home-register-errors" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_register_password" :value="__('Password')" />
                                        <x-text-input id="home_register_password" class="mt-2 w-full" type="password" name="password" required autocomplete="new-password" :invalid="$errors->getBag('register')->has('password')" error-id="home-register-errors" />
                                    </div>

                                    <div>
                                        <x-input-label for="home_register_password_confirmation" :value="__('Confirm Password')" />
                                        <x-text-input id="home_register_password_confirmation" class="mt-2 w-full" type="password" name="password_confirmation" required autocomplete="new-password" :invalid="$errors->getBag('register')->has('password_confirmation')" error-id="home-register-errors" />
                                    </div>

                                    <x-primary-button class="w-full justify-center">
                                        {{ __('Register') }}
                                    </x-primary-button>
                                </form>
                            </div>
                        @endauth
                    </section>
                </section>
            </main>
        </div>

        <x-flash-toasts />
    </body>
</html>
