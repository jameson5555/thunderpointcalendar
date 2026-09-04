<x-guest-layout title="Reset password">
    <div class="mb-6">
        <h1 class="tp-heading-page">Reset password</h1>
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

        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <a class="tp-link text-sm" href="{{ route('home', ['auth' => 'login']) }}">
                {{ __('Back to sign in') }}
            </a>

            <x-primary-button>
                {{ __('Send Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
