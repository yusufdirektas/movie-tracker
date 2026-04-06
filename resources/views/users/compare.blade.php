@extends('layouts.app')

@section('title', $user->name . ' ile Karşılaştır')

@section('content')
<div class="container mx-auto">

    {{-- HEADER --}}
    <div class="mb-8">
        <a href="{{ route('users.show', $user) }}" class="text-indigo-400 hover:text-indigo-300 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Profile Dön
        </a>

        <div class="flex items-center justify-center gap-8 mb-6">
            {{-- Ben --}}
            <div class="text-center">
                <img src="{{ auth()->user()->avatar_url }}"
                     alt="{{ auth()->user()->name }}"
                     class="w-20 h-20 rounded-full object-cover mx-auto shadow-lg ring-4 ring-indigo-500/50">
                <p class="mt-2 font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="text-sm text-slate-400">{{ $stats['my_total'] }} film</p>
            </div>

            {{-- VS --}}
            <div class="text-center">
                <div class="relative w-24 h-24 mx-auto">
                    {{-- Circular Progress --}}
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="48" cy="48" r="40"
                                stroke="currentColor"
                                stroke-width="8"
                                fill="transparent"
                                class="text-slate-700"/>
                        <circle cx="48" cy="48" r="40"
                                stroke="url(#similarity-gradient)"
                                stroke-width="8"
                                fill="transparent"
                                stroke-dasharray="251"
                                stroke-dashoffset="{{ 251 - (251 * $stats['similarity'] / 100) }}"
                                stroke-linecap="round"
                                class="transition-all duration-1000"/>
                        <defs>
                            <linearGradient id="similarity-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#818cf8"/>
                                <stop offset="100%" stop-color="#c084fc"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-2xl font-black text-white">%{{ $stats['similarity'] }}</span>
                    </div>
                </div>
                <p class="text-sm text-slate-400 mt-1">Uyum</p>
            </div>

            {{-- Karşı Taraf --}}
            <div class="text-center">
                <img src="{{ $user->avatar_url }}"
                     alt="{{ $user->name }}"
                     class="w-20 h-20 rounded-full object-cover mx-auto shadow-lg ring-4 ring-purple-500/50">
                <p class="mt-2 font-bold text-white">{{ $user->name }}</p>
                <p class="text-sm text-slate-400">{{ $stats['their_total'] }} film</p>
            </div>
        </div>

        {{-- İstatistik Kartları --}}
        <div class="grid grid-cols-3 gap-4 max-w-xl mx-auto">
            <div class="bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 border border-emerald-500/30 rounded-xl p-4 text-center">
                <p class="text-3xl font-black text-emerald-400">{{ $stats['common_count'] }}</p>
                <p class="text-sm text-slate-400">Ortak Film</p>
            </div>
            <div class="bg-gradient-to-br from-indigo-500/20 to-indigo-600/10 border border-indigo-500/30 rounded-xl p-4 text-center">
                <p class="text-3xl font-black text-indigo-400">{{ $stats['only_mine_count'] }}</p>
                <p class="text-sm text-slate-400">Sadece Sen</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500/20 to-purple-600/10 border border-purple-500/30 rounded-xl p-4 text-center">
                <p class="text-3xl font-black text-purple-400">{{ $stats['only_theirs_count'] }}</p>
                <p class="text-sm text-slate-400">Sadece {{ Str::limit($user->name, 10) }}</p>
            </div>
        </div>
    </div>

    {{-- TAB NAVİGASYON --}}
    <div x-data="{ activeTab: 'common' }" class="space-y-6">
        <div class="flex justify-center gap-2 border-b border-slate-700 pb-4">
            <button @click="activeTab = 'common'"
                    :class="activeTab === 'common' ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/50' : 'bg-slate-800/50 text-slate-400 border-slate-700 hover:border-slate-600'"
                    class="px-6 py-2.5 rounded-xl font-medium border transition-all">
                <i class="fas fa-intersection mr-2"></i>
                Ortak ({{ $stats['common_count'] }})
            </button>
            <button @click="activeTab = 'mine'"
                    :class="activeTab === 'mine' ? 'bg-indigo-500/20 text-indigo-400 border-indigo-500/50' : 'bg-slate-800/50 text-slate-400 border-slate-700 hover:border-slate-600'"
                    class="px-6 py-2.5 rounded-xl font-medium border transition-all">
                <i class="fas fa-user mr-2"></i>
                Sadece Ben ({{ $stats['only_mine_count'] }})
            </button>
            <button @click="activeTab = 'theirs'"
                    :class="activeTab === 'theirs' ? 'bg-purple-500/20 text-purple-400 border-purple-500/50' : 'bg-slate-800/50 text-slate-400 border-slate-700 hover:border-slate-600'"
                    class="px-6 py-2.5 rounded-xl font-medium border transition-all">
                <i class="fas fa-user-friends mr-2"></i>
                Sadece {{ Str::limit($user->name, 10) }} ({{ $stats['only_theirs_count'] }})
            </button>
        </div>

        {{-- ORTAK FİLMLER --}}
        <div x-show="activeTab === 'common'" x-transition>
            @if($commonMovies->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($commonMovies as $movie)
                        <a href="{{ route('movies.show', $movie) }}" class="group">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden shadow-lg ring-2 ring-emerald-500/30 group-hover:ring-emerald-500/60 transition-all">
                                @if($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                         alt="{{ $movie->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                        <i class="fas fa-film text-slate-700 text-3xl"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                    <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if($stats['common_count'] > 20)
                    <p class="text-center text-slate-500 text-sm mt-4">
                        ve {{ $stats['common_count'] - 20 }} film daha...
                    </p>
                @endif
            @else
                <div class="text-center py-12">
                    <i class="fas fa-film text-slate-700 text-5xl mb-4"></i>
                    <p class="text-slate-400">Henüz ortak izlenen film yok.</p>
                    <p class="text-slate-500 text-sm mt-2">Film izledikçe burada ortak zevkleriniz görünecek!</p>
                </div>
            @endif
        </div>

        {{-- SADECE BEN --}}
        <div x-show="activeTab === 'mine'" x-transition style="display: none;">
            @if($onlyMineMovies->isNotEmpty())
                <p class="text-slate-400 text-sm mb-4 text-center">
                    {{ $user->name }}'in izlemediği ama senin izlediğin filmler. Önerebilirsin! 😉
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($onlyMineMovies as $movie)
                        <a href="{{ route('movies.show', $movie) }}" class="group">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden shadow-lg ring-2 ring-indigo-500/30 group-hover:ring-indigo-500/60 transition-all">
                                @if($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                         alt="{{ $movie->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                        <i class="fas fa-film text-slate-700 text-3xl"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                    <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
                @if($stats['only_mine_count'] > 20)
                    <p class="text-center text-slate-500 text-sm mt-4">
                        ve {{ $stats['only_mine_count'] - 20 }} film daha...
                    </p>
                @endif
            @else
                <div class="text-center py-12">
                    <i class="fas fa-check-circle text-emerald-500 text-5xl mb-4"></i>
                    <p class="text-slate-400">{{ $user->name }} senin izlediğin tüm filmleri izlemiş!</p>
                </div>
            @endif
        </div>

        {{-- SADECE O --}}
        <div x-show="activeTab === 'theirs'" x-transition style="display: none;">
            @if($onlyTheirsMovies->isNotEmpty())
                <p class="text-slate-400 text-sm mb-4 text-center">
                    {{ $user->name }}'in izleyip senin henüz izlemediğin filmler. Keşfet! 🎬
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                    @foreach($onlyTheirsMovies as $movie)
                        <div class="group relative">
                            <div class="relative aspect-[2/3] rounded-xl overflow-hidden shadow-lg ring-2 ring-purple-500/30 group-hover:ring-purple-500/60 transition-all">
                                @if($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                         alt="{{ $movie->title }}"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full bg-slate-800 flex items-center justify-center">
                                        <i class="fas fa-film text-slate-700 text-3xl"></i>
                                    </div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                    <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                                </div>

                                {{-- Watchlist'e Ekle Butonu --}}
                                <button onclick="addToWatchlist({{ $movie->tmdb_id }}, '{{ addslashes($movie->title) }}')"
                                        class="absolute top-2 right-2 w-8 h-8 rounded-full bg-black/60 text-white opacity-0 group-hover:opacity-100 transition-opacity hover:bg-indigo-500 flex items-center justify-center"
                                        title="İzleme Listeme Ekle">
                                    <i class="fas fa-plus text-sm"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($stats['only_theirs_count'] > 20)
                    <p class="text-center text-slate-500 text-sm mt-4">
                        ve {{ $stats['only_theirs_count'] - 20 }} film daha...
                    </p>
                @endif
            @else
                <div class="text-center py-12">
                    <i class="fas fa-trophy text-amber-400 text-5xl mb-4"></i>
                    <p class="text-slate-400">Sen {{ $user->name }}'in izlediği tüm filmleri izlemişsin!</p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- Watchlist'e ekleme için basit script --}}
<script>
function addToWatchlist(tmdbId, title) {
    // Search endpoint'ine yönlendir
    window.location.href = '/search?query=' + encodeURIComponent(title);
}
</script>
@endsection
