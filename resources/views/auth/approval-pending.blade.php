<x-guest-layout>
    <div class="space-y-5">
        <div class="space-y-2">
            <p class="tp-meta text-[var(--tp-lake)]">Account access</p>
            <h1 class="font-display text-3xl text-[var(--tp-bark)]">Approval pending</h1>
        </div>

        @if (session('status'))
            <div class="rounded-[1rem] border border-[rgba(26,140,145,0.26)] bg-[rgba(255,252,245,0.92)] px-4 py-3 text-sm font-semibold text-[var(--tp-pine)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-[1rem] border border-[var(--tp-border)] bg-[rgba(253,251,247,0.76)] p-4 text-sm leading-6 text-[var(--tp-muted)]">
            Once an admin approves your account, you can sign in from the home page and open the calendar. If approval has already gone through, try signing in again.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('home', ['auth' => 'login']) }}" class="tp-button-primary">Back to sign in</a>
            <a href="{{ route('home') }}" class="tp-button-secondary">Return home</a>
        </div>
    </div>
</x-guest-layout>