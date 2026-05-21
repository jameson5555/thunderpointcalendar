<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-display text-3xl text-[var(--tp-bark)]">Confirm password</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">Re-enter your password to continue.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button>
                {{ __('Confirm') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
