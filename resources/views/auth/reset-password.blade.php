<x-guest-layout title="Choose a new password">
    <div class="mb-6">
        <h1 class="font-display text-3xl text-[var(--tp-bark)]">Choose a new password</h1>
        <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">Set the password you want to use for sign in.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <x-error-summary class="mb-4" :messages="$errors->all()" />

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" :invalid="$errors->has('email')" error-id="email_error" />
            <x-input-error id="email_error" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" :invalid="$errors->has('password')" error-id="password_error" />
            <x-input-error id="password_error" :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" :invalid="$errors->has('password_confirmation')" error-id="password_confirmation_error" />

            <x-input-error id="password_confirmation_error" :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
