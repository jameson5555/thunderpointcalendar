<nav x-data="{ open: false }" class="relative z-40 border-b border-[var(--tp-border)] bg-[rgba(245,237,212,0.96)]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-20 justify-between gap-4 py-4">
            <div class="flex min-w-0 items-center gap-6">
                <div class="shrink-0">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="w-auto" />
                    </a>
                </div>

                <div class="hidden items-center gap-2 md:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Calendar') }}
                    </x-nav-link>

                    @if (Auth::user()->canAccessAdmin())
                        <x-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden md:flex md:items-center md:gap-4">
                @if (Auth::user()->canAccessAdmin())
                    <span class="tp-chip text-[var(--tp-brass)]">
                        {{ Auth::user()->isAdmin() ? 'Site Admin' : 'Poobah' }}
                    </span>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-3 rounded-full border border-[var(--tp-border)] bg-[rgba(255,252,245,0.94)] px-3 py-2 text-sm font-semibold text-[var(--tp-bark)] shadow-sm transition hover:border-[rgba(221,79,22,0.24)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full border border-[rgba(239,177,43,0.42)] bg-[rgba(255,252,245,0.96)] font-display text-lg text-[var(--tp-brass)]">
                                {{ strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <div class="text-left">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="text-xs font-medium text-[var(--tp-muted)]">{{ Auth::user()->email }}</div>
                            </div>

                            <div class="text-[var(--tp-muted)]">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center md:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full border border-[var(--tp-border)] bg-[rgba(247,240,215,0.92)] p-3 text-[var(--tp-bark)] transition hover:border-[var(--tp-border-strong)] focus:outline-none focus:ring-2 focus:ring-[var(--tp-focus)] focus:ring-offset-2 focus:ring-offset-[var(--tp-paper)]">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden">
        <div class="space-y-1 px-4 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Calendar') }}
            </x-responsive-nav-link>

            @if (Auth::user()->canAccessAdmin())
                <x-responsive-nav-link :href="route('admin.index')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-[var(--tp-border)] px-4 pb-4 pt-4">
            <div class="px-4">
                <div class="font-display text-xl text-[var(--tp-bark)]">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-[var(--tp-muted)]">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
