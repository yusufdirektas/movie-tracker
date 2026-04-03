<nav
    x-data="{
        open: false,
        toggleMenu() {
            this.open = !this.open;
        },
        closeMenu() {
            this.open = false;
        }
    }"
    x-init="$watch('open', (value) => {
        if (value) {
            $nextTick(() => $refs.firstMobileLink?.focus());
            return;
        }
        $nextTick(() => $refs.menuToggle?.focus());
    })"
    @keydown.escape.window="closeMenu()"
    class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50"
    aria-label="Ana navigasyon">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('movies.index') }}" class="group flex items-center gap-3">
                        <div
                            class="relative w-10 h-10 flex items-center justify-center bg-indigo-600 rounded-lg transform -skew-x-6 group-hover:skew-x-0 transition-all duration-300 shadow-lg shadow-indigo-600/30">
                            <i
                                class="fas fa-play text-white text-sm transform skew-x-6 group-hover:skew-x-0 transition-all"></i>
                            <div
                                class="absolute -bottom-1 -right-1 w-full h-full border-2 border-slate-700 rounded-lg -z-10 group-hover:bottom-0 group-hover:right-0 transition-all">
                            </div>
                        </div>

                        <div class="flex flex-col justify-center leading-none">
                            <span class="text-white font-black text-xl tracking-tighter italic"
                                style="font-family: 'Arial Black', sans-serif;">
                                YSD<span class="text-indigo-500">SOFT</span>
                            </span>
                            <span
                                class="text-slate-500 text-[9px] font-bold tracking-[0.3em] uppercase pl-0.5 group-hover:text-indigo-400 transition-colors">
                                Interactive
                            </span>
                        </div>
                    </a>
                </div>

                <div class="hidden sm:ml-10 sm:flex sm:items-center sm:gap-2" data-nav-group>
                    <a href="{{ route('movies.index') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.index') ? 'is-active' : '' }}"
                        aria-label="Film Arşivim"
                        data-nav-item
                        @if (request()->routeIs('movies.index')) aria-current="page" @endif>
                        <i class="fas fa-film ysd-nav-icon"></i>
                        <span class="ysd-nav-label">{{ __('Film Arşivim') }}</span>
                    </a>

                    <a href="{{ route('movies.watchlist') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.watchlist') ? 'is-active' : '' }}"
                        aria-label="İzleme Listem"
                        data-nav-item
                        @if (request()->routeIs('movies.watchlist')) aria-current="page" @endif>
                        <i class="fas fa-bookmark ysd-nav-icon text-amber-500/80"></i>
                        <span class="ysd-nav-label">{{ __('İzleme Listem') }}</span>
                    </a>

                    <a href="{{ route('movies.create') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.create') ? 'is-active' : '' }}"
                        aria-label="Film Ekle"
                        data-nav-item
                        @if (request()->routeIs('movies.create')) aria-current="page" @endif>
                        <i class="fas fa-plus ysd-nav-icon"></i>
                        <span class="ysd-nav-label">{{ __('Film Ekle') }}</span>
                    </a>

                    <a href="{{ route('movies.recommendations') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.recommendations') ? 'is-active' : '' }}"
                        aria-label="Sana Özel Öneriler"
                        data-nav-item
                        @if (request()->routeIs('movies.recommendations')) aria-current="page" @endif>
                        <i class="fas fa-magic ysd-nav-icon text-purple-400"></i>
                        <span class="ysd-nav-label">{{ __('Sana Özel Öneriler') }}</span>
                    </a>

                    <a href="{{ route('movies.now_playing') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.now_playing') ? 'is-active' : '' }}"
                        aria-label="Vizyondakiler"
                        data-nav-item
                        @if (request()->routeIs('movies.now_playing')) aria-current="page" @endif>
                        <i class="fas fa-fire ysd-nav-icon text-orange-500"></i>
                        <span class="ysd-nav-label">{{ __('Vizyondakiler') }}</span>
                    </a>

                    <a href="{{ route('collections.index') }}"
                        class="ysd-nav-item {{ request()->routeIs('collections.*') ? 'is-active' : '' }}"
                        aria-label="Koleksiyonlarım"
                        data-nav-item
                        @if (request()->routeIs('collections.*')) aria-current="page" @endif>
                        <i class="fas fa-layer-group ysd-nav-icon text-teal-400"></i>
                        <span class="ysd-nav-label">{{ __('Koleksiyonlarım') }}</span>
                    </a>

                    <a href="{{ route('movies.statistics') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.statistics') ? 'is-active' : '' }}"
                        aria-label="İstatistikler"
                        data-nav-item
                        @if (request()->routeIs('movies.statistics')) aria-current="page" @endif>
                        <i class="fas fa-chart-pie ysd-nav-icon"></i>
                        <span class="ysd-nav-label">{{ __('İstatistikler') }}</span>
                    </a>

                    {{-- 📚 Sosyal Dropdown (Feed & Keşfet) --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" @click.away="open = false"
                            class="ysd-nav-item {{ request()->routeIs('users.*') || request()->routeIs('feed') ? 'is-active' : '' }}"
                            aria-label="Sosyal menü">
                            <i class="fas fa-users ysd-nav-icon text-pink-400"></i>
                            <span class="ysd-nav-label">{{ __('Sosyal') }}</span>
                            <i class="fas fa-chevron-down text-xs ml-1 transition-transform" :class="{ 'rotate-180': open }"></i>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 mt-2 w-48 rounded-xl bg-slate-800 border border-slate-700 shadow-xl py-2 z-50"
                            style="display: none;">

                            <a href="{{ route('feed') }}"
                                class="flex items-center gap-3 px-4 py-2 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('feed') ? 'bg-slate-700 text-white' : '' }}">
                                <i class="fas fa-stream text-indigo-400"></i>
                                Aktivite Feed
                            </a>

                            <a href="{{ route('users.index') }}"
                                class="flex items-center gap-3 px-4 py-2 text-sm text-slate-300 hover:bg-slate-700 hover:text-white transition-colors {{ request()->routeIs('users.index') ? 'bg-slate-700 text-white' : '' }}">
                                <i class="fas fa-search text-pink-400"></i>
                                Kullanıcıları Keşfet
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                {{-- Active Import Badge --}}
                @if($activeImport ?? null)
                    <a href="{{ route('movies.import.history') }}"
                       data-import-badge
                       class="mr-4 relative flex items-center gap-2 px-3 py-2 text-xs font-medium rounded-xl bg-indigo-500/20 text-indigo-300 hover:bg-indigo-500/30 transition-colors"
                       title="Aktif içe aktarma: {{ $activeImport->processed_items }}/{{ $activeImport->total_items }}">
                        <i class="fas fa-sync fa-spin"></i>
                        <span class="hidden lg:inline">İçe Aktarılıyor</span>
                        <span class="font-bold" data-import-counter>{{ $activeImport->processed_items }}/{{ $activeImport->total_items }}</span>
                    </a>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-4 py-2 border border-slate-800 text-sm leading-4 font-medium rounded-xl text-slate-400 bg-slate-800 hover:text-white hover:bg-slate-700 focus:outline-none transition ease-in-out duration-150 shadow-md">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')" class="text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                            <i class="fas fa-user-circle"></i> {{ __('Profil') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('users.show', Auth::user())" class="text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                            <i class="fas fa-id-card"></i> {{ __('Profilim') }}
                        </x-dropdown-link>

                        <x-dropdown-link :href="route('feed')" class="text-slate-700 hover:bg-slate-100 flex items-center gap-2">
                            <i class="fas fa-rss"></i> {{ __('Aktivite Akışı') }}
                        </x-dropdown-link>

                        <hr class="my-1 border-slate-200">

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                class="text-red-600 hover:bg-red-50 flex items-center gap-2">
                                <i class="fas fa-sign-out-alt"></i> {{ __('Çıkış Yap') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button x-ref="menuToggle" @click="toggleMenu()"
                    class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/70 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 transition duration-150 ease-in-out"
                    :aria-expanded="open.toString()"
                    aria-controls="mobile-menu"
                    aria-label="Mobil menüyü aç/kapat">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="mobile-menu" :class="{ 'block': open, 'hidden': !open }" :aria-hidden="(!open).toString()" class="hidden sm:hidden bg-slate-900 border-t border-slate-800">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('movies.index')" :active="request()->routeIs('movies.index')"
                x-ref="firstMobileLink"
                x-on:click="closeMenu()"
                aria-label="Film Arşivim"
                :aria-current="request()->routeIs('movies.index') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-film w-5 text-center"></i> {{ __('Film Arşivim') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.watchlist')" :active="request()->routeIs('movies.watchlist')"
                x-on:click="closeMenu()"
                aria-label="İzleme Listem"
                :aria-current="request()->routeIs('movies.watchlist') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-amber-500 flex items-center gap-2">
                <i class="fas fa-bookmark w-5 text-center text-amber-500/70"></i> {{ __('İzleme Listem') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.create')" :active="request()->routeIs('movies.create')"
                x-on:click="closeMenu()"
                aria-label="Film Ekle"
                :aria-current="request()->routeIs('movies.create') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-plus w-5 text-center"></i> {{ __('Film Ekle') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.recommendations')" :active="request()->routeIs('movies.recommendations')"
                x-on:click="closeMenu()"
                aria-label="Sana Özel Öneriler"
                :aria-current="request()->routeIs('movies.recommendations') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-magic w-5 text-center text-purple-400"></i> {{ __('Sana Özel Öneriler') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.now_playing')" :active="request()->routeIs('movies.now_playing')"
                x-on:click="closeMenu()"
                aria-label="Vizyondakiler"
                :aria-current="request()->routeIs('movies.now_playing') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-fire w-5 text-center text-orange-500"></i> {{ __('Vizyondakiler') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('collections.index')" :active="request()->routeIs('collections.*')"
                x-on:click="closeMenu()"
                aria-label="Koleksiyonlarım"
                :aria-current="request()->routeIs('collections.*') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-teal-500 flex items-center gap-2">
                <i class="fas fa-layer-group w-5 text-center text-teal-400"></i> {{ __('Koleksiyonlarım') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.statistics')" :active="request()->routeIs('movies.statistics')"
                x-on:click="closeMenu()"
                aria-label="İstatistikler"
                :aria-current="request()->routeIs('movies.statistics') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-chart-pie w-5 text-center"></i> {{ __('İstatistikler') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*') || request()->routeIs('feed')"
                x-on:click="closeMenu()"
                aria-label="Keşfet"
                :aria-current="request()->routeIs('users.*') || request()->routeIs('feed') ? 'page' : null"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-pink-500 flex items-center gap-2">
                <i class="fas fa-users w-5 text-center text-pink-400"></i> {{ __('Keşfet') }}
            </x-responsive-nav-link>

            {{-- Mobile Active Import Badge --}}
            @if($activeImport ?? null)
                <a href="{{ route('movies.import.history') }}"
                   @click="closeMenu()"
                   class="flex px-4 py-3 bg-indigo-500/20 border-l-4 border-indigo-500 text-indigo-300 items-center gap-3">
                    <i class="fas fa-sync fa-spin w-5 text-center"></i>
                    <span>İçe Aktarılıyor: <span class="font-bold">{{ $activeImport->processed_items }}/{{ $activeImport->total_items }}</span></span>
                </a>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-slate-800">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" x-on:click="closeMenu()" class="text-slate-300 hover:text-white hover:bg-slate-800 flex items-center gap-2">
                    <i class="fas fa-user-circle w-5 text-center"></i> {{ __('Profil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        x-on:click="closeMenu()"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();"
                        class="text-red-400 hover:text-red-300 hover:bg-slate-800 flex items-center gap-2">
                        <i class="fas fa-sign-out-alt w-5 text-center"></i> {{ __('Çıkış Yap') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
