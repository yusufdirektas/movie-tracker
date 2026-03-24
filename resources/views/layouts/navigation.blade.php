<nav x-data="{ open: false }" class="bg-slate-900 border-b border-slate-800 sticky top-0 z-50">
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

                <div class="hidden sm:ml-10 sm:flex sm:items-center sm:gap-2">
                    <a href="{{ route('movies.index') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.index') ? 'is-active' : '' }}">
                        <i class="fas fa-film ysd-nav-icon"></i>
                        <span class="ysd-nav-label">{{ __('Film Arşivim') }}</span>
                    </a>

                    <a href="{{ route('movies.watchlist') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.watchlist') ? 'is-active' : '' }}">
                        <i class="fas fa-bookmark ysd-nav-icon text-amber-500/80"></i>
                        <span class="ysd-nav-label">{{ __('İzleme Listem') }}</span>
                    </a>

                    <a href="{{ route('movies.create') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.create') ? 'is-active' : '' }}">
                        <i class="fas fa-plus ysd-nav-icon"></i>
                        <span class="ysd-nav-label">{{ __('Film Ekle') }}</span>
                    </a>

                    <a href="{{ route('movies.recommendations') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.recommendations') ? 'is-active' : '' }}">
                        <i class="fas fa-magic ysd-nav-icon text-purple-400"></i>
                        <span class="ysd-nav-label">{{ __('Sana Özel Öneriler') }}</span>
                    </a>

                    <a href="{{ route('movies.now_playing') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.now_playing') ? 'is-active' : '' }}">
                        <i class="fas fa-fire ysd-nav-icon text-orange-500"></i>
                        <span class="ysd-nav-label">{{ __('Vizyondakiler') }}</span>
                    </a>

                    <a href="{{ route('collections.index') }}"
                        class="ysd-nav-item {{ request()->routeIs('collections.*') ? 'is-active' : '' }}">
                        <i class="fas fa-layer-group ysd-nav-icon text-teal-400"></i>
                        <span class="ysd-nav-label">{{ __('Koleksiyonlarım') }}</span>
                    </a>

                    <a href="{{ route('movies.statistics') }}"
                        class="ysd-nav-item {{ request()->routeIs('movies.statistics') ? 'is-active' : '' }}">
                        <i class="fas fa-chart-pie ysd-nav-icon"></i>
                        <span class="ysd-nav-label">{{ __('İstatistikler') }}</span>
                    </a>

                    <a href="{{ route('users.index') }}"
                        class="ysd-nav-item {{ request()->routeIs('users.*') || request()->routeIs('feed') ? 'is-active' : '' }}">
                        <i class="fas fa-users ysd-nav-icon text-pink-400"></i>
                        <span class="ysd-nav-label">{{ __('Keşfet') }}</span>
                    </a>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
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
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-800 focus:outline-none transition duration-150 ease-in-out">
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

    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden bg-slate-900 border-t border-slate-800">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('movies.index')" :active="request()->routeIs('movies.index')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-film w-5 text-center"></i> {{ __('Film Arşivim') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.watchlist')" :active="request()->routeIs('movies.watchlist')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-amber-500 flex items-center gap-2">
                <i class="fas fa-bookmark w-5 text-center text-amber-500/70"></i> {{ __('İzleme Listem') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.create')" :active="request()->routeIs('movies.create')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-plus w-5 text-center"></i> {{ __('Film Ekle') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.recommendations')" :active="request()->routeIs('movies.recommendations')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-magic w-5 text-center text-purple-400"></i> {{ __('Sana Özel Öneriler') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.now_playing')" :active="request()->routeIs('movies.now_playing')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500 flex items-center gap-2">
                <i class="fas fa-fire w-5 text-center text-orange-500"></i> {{ __('Vizyondakiler') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('collections.index')" :active="request()->routeIs('collections.*')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-teal-500 flex items-center gap-2">
                <i class="fas fa-layer-group w-5 text-center text-teal-400"></i> {{ __('Koleksiyonlarım') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('movies.statistics')" :active="request()->routeIs('movies.statistics')"
                class="text-indigo-400 font-bold hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500">
                <i class="fas fa-chart-pie w-5 text-center mr-1"></i> {{ __('İstatistikler') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*') || request()->routeIs('feed')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-pink-500 flex items-center gap-2">
                <i class="fas fa-users w-5 text-center text-pink-400"></i> {{ __('Keşfet') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-slate-800">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="text-slate-300 hover:text-white hover:bg-slate-800 flex items-center gap-2">
                    <i class="fas fa-user-circle w-5 text-center"></i> {{ __('Profil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
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
