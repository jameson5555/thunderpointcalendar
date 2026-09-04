@if (config('app.env') === 'staging')
    <div class="border-b-2 border-[var(--tp-brown-strong)] bg-[var(--tp-orange)] px-3 py-1.5 text-center text-xs font-extrabold uppercase tracking-[0.16em] text-white sm:px-6" role="status">
        Staging environment
    </div>
@endif
