<x-guest-layout>
    <div class="mb-6">
        <h2 class="font-display text-3xl text-[var(--tp-bark)]">Verify email</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--tp-muted)]">Use the link in your email before signing in.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-[1rem] border border-[rgba(88,109,91,0.18)] bg-[rgba(88,109,91,0.08)] px-4 py-3 text-sm font-semibold text-[var(--tp-pine)]">
            {{ __('A new verification link has been sent.') }}
        </div>
    @endif

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

            <button type="submit" class="tp-link text-sm focus:outline-none">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
