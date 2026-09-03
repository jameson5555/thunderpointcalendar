<x-guest-layout title="Register">
    <div class="mb-6">
        <h1 class="font-display text-3xl text-[var(--tp-bark)]">Register</h1>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <x-error-summary class="mb-4" :messages="$errors->all()" />

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" :invalid="$errors->has('name')" error-id="name_error" />
            <x-input-error id="name_error" :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" :invalid="$errors->has('email')" error-id="email_error" />
            <x-input-error id="email_error" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" :invalid="$errors->has('password')" error-id="password_error" />

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

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a class="tp-link text-sm" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="justify-center sm:ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
