<x-guest-layout title="Reset password">
    <div class="mb-6">
        <h1 class="font-display text-3xl text-[var(--tp-bark)]">Reset password</h1>
    </div>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <x-error-summary class="mb-4" :messages="$errors->all()" />

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" :invalid="$errors->has('email')" error-id="email_error" />
            <x-input-error id="email_error" :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-end">
            <x-primary-button>
                {{ __('Send Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
