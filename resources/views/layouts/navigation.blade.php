<div
    x-data="mobileNavigation"
    x-init="init()"
    @appinstalled.window="handleAppInstalled()"
    @keydown.escape.window="closeOnEscape($event)"
>
    <nav
        class="relative z-40 border-b border-[var(--tp-border)] bg-[var(--tp-shell)]"
        aria-label="Primary navigation"
        @click.outside="closeMenu()"
    >
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex min-h-[3.75rem] justify-between gap-4 py-2 md:min-h-20 md:py-4">
                <div class="flex min-w-0 items-center gap-6">
                    <div class="shrink-0">
                        <a href="{{ route('dashboard') }}" aria-label="Thunderpoint calendar">
                            <x-application-logo class="w-auto" />
                        </a>
                    </div>

                    <div class="hidden items-center gap-2 md:flex">
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Calendar') }}
                        </x-nav-link>

                        @if (Auth::user()->canAccessAdmin())
                            <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                                {{ __('Manage') }}
                            </x-nav-link>
                        @endif
                    </div>
                </div>

                <div class="hidden md:flex md:items-center md:gap-4">
                    @if (Auth::user()->canAccessAdmin())
                        <span class="tp-chip text-[var(--tp-text-accent)]">
                            {{ Auth::user()->isAdmin() ? 'Site Admin' : 'Poobah' }}
                        </span>
                    @endif

                    <x-dropdown align="right" width="48" id="account-menu">
                        <x-slot name="trigger">
                            <button type="button" aria-controls="account-menu" :aria-expanded="open.toString()" class="inline-flex min-h-11 items-center gap-3 rounded-full border border-[var(--tp-border)] bg-[var(--tp-surface)] px-3 py-2 text-sm font-semibold text-[var(--tp-bark)] shadow-sm transition hover:border-[var(--tp-border-strong)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-[var(--tp-text-accent)] bg-[var(--tp-surface-raised)] font-display text-lg text-[var(--tp-text-accent)]" aria-hidden="true">
                                    {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="min-w-0 text-left">
                                    <span class="block text-xs font-bold uppercase tracking-[0.12em] text-[var(--tp-muted)]">{{ __('Account') }}</span>
                                    <span class="block max-w-36 truncate">{{ Auth::user()->name }}</span>
                                </span>

                                <svg aria-hidden="true" class="h-4 w-4 shrink-0 fill-current text-[var(--tp-muted)] transition" :class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-[var(--tp-border)] px-4 py-3">
                                <div class="truncate text-sm font-semibold text-[var(--tp-bark)]">{{ Auth::user()->name }}</div>
                                <div class="truncate text-xs text-[var(--tp-muted)]">{{ Auth::user()->email }}</div>
                            </div>

                            <div class="py-1">
                                <x-dropdown-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                            onclick="event.preventDefault();
                                                        this.closest('form').submit();">
                                        {{ __('Sign out') }}
                                    </x-dropdown-link>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="flex items-center md:hidden">
                    <button
                        x-ref="mobileToggle"
                        type="button"
                        @click="toggleMenu()"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-[0.9rem] border border-[var(--tp-border-strong)] px-3.5 py-2 text-base font-bold text-[var(--tp-bark)] shadow-sm transition duration-200 hover:bg-[var(--tp-surface-raised)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)] motion-reduce:transition-none"
                        :class="open ? 'bg-[var(--tp-surface-raised)] shadow-md' : 'bg-[var(--tp-surface)]'"
                        aria-controls="mobile-navigation"
                        :aria-expanded="open.toString()"
                    >
                        <svg aria-hidden="true" class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path x-show="! open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span x-text="open ? '{{ __('Close') }}' : '{{ __('Menu') }}'">{{ __('Menu') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition-opacity ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-x-0 top-full z-40 h-[calc(100dvh-3.75rem)] touch-none bg-[rgba(78,59,46,0.26)] md:hidden"
            aria-hidden="true"
            data-mobile-menu-scrim
            @click="closeMenu()"
        ></div>

        <div
            id="mobile-navigation"
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200 motion-reduce:transition-none"
            x-transition:enter-start="-translate-y-2 opacity-0 motion-reduce:transform-none"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150 motion-reduce:transition-none"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-2 opacity-0 motion-reduce:transform-none"
            class="absolute inset-x-0 top-full z-50 max-h-[calc(100dvh-3.75rem)] overflow-y-auto overscroll-contain border-y border-[var(--tp-border-strong)] bg-[#fdfbf7] shadow-[0_16px_30px_rgba(70,45,27,0.16)] md:hidden"
            data-mobile-menu-panel
        >
            <div class="mx-auto max-w-7xl space-y-4 px-4 pb-5 pt-4 sm:px-6">
                <section aria-labelledby="mobile-go-to-heading">
                    <h2 id="mobile-go-to-heading" class="tp-meta px-2 text-[var(--tp-bark)]">{{ __('Go to') }}</h2>
                    <div class="mt-2 space-y-1">
                        <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" @click="closeMenu()">
                            {{ __('Calendar') }}
                        </x-responsive-nav-link>

                        @if (Auth::user()->canAccessAdmin())
                            <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')" @click="closeMenu()">
                                {{ __('Manage') }}
                            </x-responsive-nav-link>
                        @endif
                    </div>
                </section>

                <section class="border-t border-[var(--tp-border)] pt-4" aria-labelledby="mobile-account-heading">
                    <h2 id="mobile-account-heading" class="tp-meta px-2 text-[var(--tp-bark)]">{{ __('Your account') }}</h2>
                    <div class="mt-2 space-y-1">
                        <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')" @click="closeMenu()">
                            {{ __('Profile') }}
                        </x-responsive-nav-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex min-h-12 w-full items-center rounded-[1rem] border-2 border-transparent px-4 py-3 text-start text-base font-semibold text-[var(--tp-muted)] transition duration-150 ease-in-out hover:bg-[var(--tp-surface-raised)] hover:text-[var(--tp-bark)]">
                                {{ __('Sign out') }}
                            </button>
                        </form>
                    </div>
                </section>

                <section
                    x-show="canOfferInstall"
                    x-cloak
                    class="border-t border-[var(--tp-border)] pt-4"
                    data-install-section
                >
                    <button
                        type="button"
                        @click="addToPhone()"
                        class="flex min-h-12 w-full items-center justify-center gap-2 rounded-[0.9rem] border border-[var(--tp-border-strong)] bg-[var(--tp-surface-raised)] px-4 py-3 text-center text-base font-bold text-[var(--tp-bark)] shadow-sm transition duration-150 hover:bg-[var(--tp-surface-muted)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]"
                    >
                        <svg aria-hidden="true" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none">
                            <path d="M8 3.75h8A2.25 2.25 0 0118.25 6v12A2.25 2.25 0 0116 20.25H8A2.25 2.25 0 015.75 18V6A2.25 2.25 0 018 3.75zM9.5 17.25h5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
                            <path d="M12 7v6m0 0l-2.25-2.25M12 13l2.25-2.25" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span>{{ __('Add Thunderpoint to your phone') }}</span>
                    </button>
                </section>
            </div>
        </div>
    </nav>

    <x-modal name="install-guidance" maxWidth="sm" labelledby="install-guidance-title">
        <div class="p-6 sm:p-7">
            <h2 id="install-guidance-title" tabindex="-1" autofocus class="font-display text-2xl text-[var(--tp-bark)]">
                {{ __('Add Thunderpoint to your phone') }}
            </h2>

            <div x-show="isAppleMobile" class="mt-4 text-base leading-7 text-[var(--tp-muted)]">
                <p>{{ __('Use your browser’s Share menu to add Thunderpoint:') }}</p>
                <ol class="mt-3 list-decimal space-y-2 ps-6">
                    <li>{{ __('Tap the Share button.') }}</li>
                    <li>{{ __('Choose Add to Home Screen.') }}</li>
                    <li>{{ __('Tap Add.') }}</li>
                </ol>
            </div>

            <div x-show="! isAppleMobile" class="mt-4 text-base leading-7 text-[var(--tp-muted)]">
                <p>{{ __('Open your browser menu, then choose Install app or Add to Home Screen.') }}</p>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" class="tp-button-primary" @click="$dispatch('close-modal', 'install-guidance')">
                    {{ __('Done') }}
                </button>
            </div>
        </div>
    </x-modal>
</div>
