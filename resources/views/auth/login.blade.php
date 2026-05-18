<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <h2 class="font-display text-3xl text-[var(--tp-bark)]">Welcome back</h2>
        <p class="mt-2 text-sm leading-6 text-[rgba(61,52,39,0.74)]">Sign in to view shared bookings, draft your dates, and keep Thunderpoint coordinated.</p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-[var(--tp-lake)] underline underline-offset-4 transition hover:text-[var(--tp-bark)] focus:outline-none" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="justify-center sm:ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <p class="mt-6 text-sm leading-6 text-[rgba(61,52,39,0.72)]">
            Need access?
            <a href="{{ route('register') }}" class="font-semibold text-[var(--tp-lake)] underline underline-offset-4">Create an account and wait for approval</a>.
        </p>
    </form>
</x-guest-layout>
