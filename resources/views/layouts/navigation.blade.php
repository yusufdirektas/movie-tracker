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

                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    <x-nav-link :href="route('movies.index')" :active="request()->routeIs('movies.index')"
                        class="text-slate-300 hover:text-white hover:border-indigo-500 focus:text-white focus:border-indigo-500 transition-colors">
                        {{ __('Film Arşivim') }}
                    </x-nav-link>
                    <x-nav-link :href="route('movies.create')" :active="request()->routeIs('movies.create')"
                        class="text-slate-300 hover:text-white hover:border-indigo-500 focus:text-white focus:border-indigo-500 transition-colors">
                        {{ __('Film Ekle') }}
                    </x-nav-link>
                    <x-nav-link :href="route('movies.recommendations')" :active="request()->routeIs('movies.recommendations')">
                        {{ __('Sana Özel Öneriler') }}
                    </x-nav-link>
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
                        <x-dropdown-link :href="route('profile.edit')" class="text-slate-700 hover:bg-slate-100">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                class="text-red-600 hover:bg-red-50">
                                {{ __('Çıkış Yap') }}
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
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500">
                {{ __('Film Arşivim') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('movies.create')" :active="request()->routeIs('movies.create')"
                class="text-slate-300 hover:text-white hover:bg-slate-800 border-l-4 border-transparent hover:border-indigo-500">
                {{ __('Film Ekle') }}
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-slate-800">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="text-slate-300 hover:text-white hover:bg-slate-800">
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();"
                        class="text-red-400 hover:text-red-300 hover:bg-slate-800">
                        {{ __('Çıkış Yap') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
