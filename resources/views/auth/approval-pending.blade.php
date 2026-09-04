<x-guest-layout title="Approval pending">
    <div class="space-y-5">
        <div class="space-y-2">
            <p class="tp-meta text-[var(--tp-status)]">Account access</p>
            <h1 class="font-display text-3xl text-[var(--tp-bark)]">Approval pending</h1>
        </div>

        <div class="rounded-[1rem] border border-[var(--tp-border)] bg-[var(--tp-surface-raised)] p-4 text-sm leading-6 text-[var(--tp-muted)]">
            Once an admin approves your account, you can sign in from the home page and open the calendar. If approval has already gone through, try signing in again.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <a href="{{ route('home') }}" class="tp-button-secondary">Return home</a>
        </div>
    </div>
</x-guest-layout>
