<x-guest-layout title="Verify email">
    <div class="mb-6">
        <h1 class="tp-heading-page">Verify email</h1>
        <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">Use the link in your email before signing in.</p>
    </div>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="tp-link text-sm">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
