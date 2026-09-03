<x-guest-layout title="Confirm password">
    <div class="mb-6">
        <h1 class="font-display text-3xl text-[var(--tp-bark)]">Confirm password</h1>
        <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">Re-enter your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <x-error-summary class="mb-4" :messages="$errors->all()" />

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" :invalid="$errors->has('password')" error-id="password_error" />

            <x-input-error id="password_error" :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
