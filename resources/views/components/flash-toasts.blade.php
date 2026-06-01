@php
    $status = session('status');
    $validationErrors = $errors->getBag('default')->all();

    $resolvedStatus = match ($status) {
        'profile-updated' => __('Profile updated.'),
        'password-updated' => __('Password updated.'),
        'verification-link-sent' => __('A new verification link has been sent to your email address.'),
        default => $status,
    };

    $flashToasts = collect([
        ['message' => $resolvedStatus, 'variant' => 'success'],
        ['message' => session('success'), 'variant' => 'success'],
        ['message' => session('error'), 'variant' => 'error'],
    ])->merge(
        collect($validationErrors)->map(fn (string $message) => ['message' => $message, 'variant' => 'error'])
    )
        ->filter(fn (array $toast) => filled($toast['message']))
        ->unique(fn (array $toast) => sprintf('%s:%s', $toast['variant'], $toast['message']))
        ->values()
        ->map(fn (array $toast, int $index) => [
            'id' => $index + 1,
            ...$toast,
        ]);
@endphp

@if ($flashToasts->isNotEmpty())
    <div
        x-data="flashToasts(@js($flashToasts->all()))"
        x-init="start()"
        class="pointer-events-none fixed inset-0 z-[90] flex items-center justify-center px-4 py-6 -translate-y-16 sm:-translate-y-20"
        data-flash-toast-region
        aria-live="polite"
        aria-atomic="true"
    >
        <div class="flex w-full max-w-md flex-col gap-3">
            <template x-for="toast in toasts" :key="toast.id">
                <section
                    x-cloak
                    x-show="toast.visible"
                    x-transition:enter="transform ease-out duration-300"
                    x-transition:enter-start="scale-95 opacity-0"
                    x-transition:enter-end="scale-100 opacity-100"
                    x-transition:leave="transform ease-in duration-200"
                    x-transition:leave-start="scale-100 opacity-100"
                    x-transition:leave-end="scale-95 opacity-0"
                    :class="toast.variant === 'error'
                        ? 'border-[rgba(122,74,86,0.72)] bg-[rgba(255,244,238,0.82)] text-[var(--tp-bark)]'
                        : 'border-[rgba(26,140,145,0.72)] bg-[rgba(255,252,245,0.82)] text-[var(--tp-pine)]'"
                    class="pointer-events-auto overflow-hidden rounded-[1.25rem] border-[3px] px-4 py-4 shadow-[0_18px_40px_rgba(95,72,56,0.16)] backdrop-blur-md backdrop-saturate-150"
                    role="status"
                >
                    <div class="flex items-start gap-3">
                        <div
                            class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full"
                            :class="toast.variant === 'error' ? 'bg-[var(--tp-ember)]' : 'bg-[var(--tp-pine)]'"
                        ></div>

                        <p class="flex-1 text-sm font-semibold leading-6" x-text="toast.message"></p>

                        <button
                            type="button"
                            class="rounded-full p-1 text-[var(--tp-muted)] transition hover:text-[var(--tp-bark)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)]"
                            @click="dismiss(toast.id)"
                        >
                            <span class="sr-only">Dismiss notification</span>
                            <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M4 4L12 12M12 4L4 12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                            </svg>
                        </button>
                    </div>
                </section>
            </template>
        </div>
    </div>
@endif