<x-guest-layout>
    <div class="space-y-5">
        <div>
            <h2 class="font-display text-3xl text-[var(--tp-bark)]">Approval pending</h2>
        </div>

        @if (session('status'))
            <div class="rounded-[1rem] border border-[rgba(88,109,91,0.18)] bg-[rgba(88,109,91,0.08)] px-4 py-3 text-sm font-semibold text-[var(--tp-pine)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.7)] p-4 text-sm leading-6 text-[var(--tp-muted)]">
            If approval has already gone through, try signing in again.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('login') }}" class="tp-button-secondary">Back to sign in</a>
            <a href="{{ route('home') }}" class="tp-button-ghost">Return home</a>
        </div>
    </div>
</x-guest-layout>