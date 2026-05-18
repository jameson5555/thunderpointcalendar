<x-guest-layout>
    <div class="space-y-5">
        <div>
            <h2 class="font-display text-3xl text-[var(--tp-bark)]">Approval pending</h2>
            <p class="mt-2 text-sm leading-6 text-[rgba(61,52,39,0.74)]">Accounts are reviewed before calendar access is granted. Once approved, you can sign in and book your stay.</p>
        </div>

        @if (session('status'))
            <div class="rounded-[1.2rem] border border-[rgba(49,91,63,0.16)] bg-[rgba(49,91,63,0.08)] px-4 py-3 text-sm font-semibold text-[var(--tp-pine)]">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-[1.3rem] border border-[rgba(61,52,39,0.1)] bg-white/70 p-4 text-sm leading-6 text-[rgba(61,52,39,0.76)]">
            If you already have approval, try signing in again. If not, the admin will need to approve your account before the calendar becomes visible.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <a href="{{ route('login') }}" class="inline-flex justify-center rounded-full bg-[var(--tp-lake)] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[var(--tp-pine)]">Back to sign in</a>
            <a href="{{ route('home') }}" class="inline-flex justify-center rounded-full border border-[rgba(61,52,39,0.16)] bg-white/70 px-5 py-3 text-sm font-semibold text-[var(--tp-bark)] transition hover:border-[var(--tp-lake)] hover:text-[var(--tp-lake)]">Return home</a>
        </div>
    </div>
</x-guest-layout>