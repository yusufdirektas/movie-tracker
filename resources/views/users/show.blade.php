@extends('layouts.app')

@section('title', $user->name . ' - Profil')

@section('content')
<div class="container mx-auto">

    {{-- PROFİL HEADER --}}
    <div class="bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700 rounded-3xl p-8 mb-8 relative overflow-hidden">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <div class="relative flex flex-col md:flex-row items-center md:items-start gap-6">
            {{-- Avatar --}}
            <img src="{{ $user->avatar_url }}"
                 alt="{{ $user->name }}"
                 class="w-24 h-24 md:w-32 md:h-32 rounded-full object-cover shadow-2xl ring-4 ring-slate-800">

            {{-- Bilgiler --}}
            <div class="flex-1 text-center md:text-left">
                <h1 class="text-3xl md:text-4xl font-black text-white mb-1">{{ $user->name }}</h1>

                {{-- Bio --}}
                @if($user->bio)
                    <p class="text-slate-400 mb-4 max-w-lg">{{ $user->bio }}</p>
                @else
                    <p class="text-slate-600 italic mb-4">Henüz bir açıklama eklenmemiş</p>
                @endif

                {{-- İstatistikler --}}
                <div class="flex flex-wrap justify-center md:justify-start gap-4 mb-4">
                    <a href="{{ route('users.followers', $user) }}"
                        class="bg-slate-800/50 hover:bg-slate-800 px-4 py-2 rounded-xl transition-colors">
                        <span class="text-white font-bold">{{ $stats['followers_count'] }}</span>
                        <span class="text-slate-500 text-sm ml-1">Takipçi</span>
                    </a>
                    <a href="{{ route('users.following', $user) }}"
                        class="bg-slate-800/50 hover:bg-slate-800 px-4 py-2 rounded-xl transition-colors">
                        <span class="text-white font-bold">{{ $stats['following_count'] }}</span>
                        <span class="text-slate-500 text-sm ml-1">Takip</span>
                    </a>
                    <div class="bg-slate-800/50 px-4 py-2 rounded-xl">
                        <span class="text-white font-bold">{{ $stats['watched_count'] }}</span>
                        <span class="text-slate-500 text-sm ml-1">Film</span>
                    </div>
                    <div class="bg-slate-800/50 px-4 py-2 rounded-xl">
                        <span class="text-white font-bold">{{ floor($stats['total_runtime'] / 60) }}</span>
                        <span class="text-slate-500 text-sm ml-1">Saat</span>
                    </div>
                </div>

                {{-- Takip Butonu (AJAX) --}}
                @if(!$isOwnProfile)
                    <div x-data="{
                        isFollowing: {{ $isFollowing ? 'true' : 'false' }},
                        followersCount: {{ $stats['followers_count'] }},
                        loading: false,

                        async toggleFollow() {
                            if (this.loading) return;
                            this.loading = true;

                            try {
                                const url = this.isFollowing
                                    ? '{{ route('users.unfollow', $user) }}'
                                    : '{{ route('users.follow', $user) }}';
                                const method = this.isFollowing ? 'DELETE' : 'POST';

                                const response = await fetch(url, {
                                    method: method,
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                    }
                                });

                                const data = await response.json();

                                if (data.success) {
                                    this.isFollowing = !this.isFollowing;
                                    this.followersCount = data.followers_count;
                                }
                            } catch (error) {
                                console.error('Follow error:', error);
                            } finally {
                                this.loading = false;
                            }
                        }
                    }" class="inline-block">
                        <button @click="toggleFollow()"
                            :disabled="loading"
                            :class="isFollowing
                                ? 'bg-slate-800 text-slate-400 hover:bg-red-500/20 hover:text-red-400 border border-slate-700 hover:border-red-500/50'
                                : 'bg-indigo-500 text-white hover:bg-indigo-600'"
                            class="px-6 py-2.5 rounded-xl font-bold text-sm transition-all disabled:opacity-50">
                            <template x-if="loading">
                                <span><i class="fas fa-spinner fa-spin mr-2"></i></span>
                            </template>
                            <template x-if="!loading && isFollowing">
                                <span><i class="fas fa-user-check mr-2"></i> Takip Ediliyor</span>
                            </template>
                            <template x-if="!loading && !isFollowing">
                                <span><i class="fas fa-user-plus mr-2"></i> Takip Et</span>
                            </template>
                        </button>

                        {{-- Karşılaştır Butonu --}}
                        <a href="{{ route('users.compare', $user) }}"
                           class="px-4 py-2.5 rounded-xl font-bold text-sm bg-slate-800/50 text-slate-400 hover:bg-purple-500/20 hover:text-purple-400 border border-slate-700 hover:border-purple-500/50 transition-all"
                           title="Film listelerinizi karşılaştır">
                            <i class="fas fa-code-compare"></i>
                            <span class="hidden sm:inline ml-2">Karşılaştır</span>
                        </a>
                    </div>
                @else
                    <a href="{{ route('profile.edit') }}"
                        class="inline-block px-6 py-2.5 rounded-xl font-bold text-sm bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white transition-all">
                        <i class="fas fa-cog mr-2"></i> Profili Düzenle
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ROZETLER --}}
    @if($earnedBadges->isNotEmpty())
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-400"></i>
                    Rozetler
                </h2>
                @if($isOwnProfile)
                    <a href="{{ route('badges.index') }}"
                       class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
                        Tümünü Gör <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                @endif
            </div>

            <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/50 border border-slate-700/50 rounded-2xl p-4">
                <div class="flex flex-wrap justify-center md:justify-start gap-2">
                    @foreach($earnedBadges->take(8) as $badge)
                        <x-badge :badge="$badge" size="sm" :earned="true" />
                    @endforeach

                    @if($earnedBadges->count() > 8)
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 text-sm font-bold">
                            +{{ $earnedBadges->count() - 8 }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- VİTRİN FİLMLERİ --}}
    @if($showcaseMovies->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                <i class="fas fa-star text-yellow-400"></i>
                Vitrin Filmleri
            </h2>
            <div class="flex flex-wrap justify-center md:justify-start gap-4">
                @foreach($showcaseMovies as $movie)
                    <div class="group relative w-32">
                        @if($movie->poster_path)
                            <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                alt="{{ $movie->title }}"
                                class="w-full aspect-[2/3] object-cover rounded-xl shadow-lg ring-2 ring-yellow-500/50 group-hover:ring-yellow-500 transition-all">
                        @else
                            <div class="w-full aspect-[2/3] bg-slate-800 rounded-xl flex items-center justify-center ring-2 ring-yellow-500/50">
                                <i class="fas fa-film text-slate-700 text-2xl"></i>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                            <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($user->show_recent_activities)
        <div data-testid="recent-activities-card" class="bg-slate-900/70 border border-slate-800 rounded-2xl p-5 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-clock-rotate-left text-cyan-400"></i>
                    Son Aktiviteler
                </h2>
                <span class="text-xs text-slate-500">{{ $recentActivities->count() }} kayıt</span>
            </div>

            @if($recentActivities->isNotEmpty())
                <div class="space-y-3">
                    @foreach($recentActivities as $activity)
                        <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-800/60 border border-slate-700/70">
                            <i class="fas {{ $activity['icon'] }} {{ $activity['icon_class'] }} mt-0.5"></i>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-100 truncate">{{ $activity['title'] }}</p>
                                <p class="text-xs text-slate-400">{{ $activity['description'] }}</p>
                            </div>
                            <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ $activity['at']->diffForHumans() }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-sm text-slate-500 bg-slate-800/40 border border-dashed border-slate-700 rounded-xl p-4">
                    Henüz gösterilecek aktivite yok.
                </div>
            @endif
        </div>
    @endif

    {{-- SEKMELER --}}
    <div x-data="{ activeTab: 'recent' }" class="mb-8">
        {{-- Sekme Başlıkları --}}
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
            <button @click="activeTab = 'recent'"
                    :class="activeTab === 'recent' ? 'bg-indigo-500 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                    class="px-4 py-2 rounded-xl font-medium text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-clock mr-2"></i> Son İzlenenler
            </button>
            <button @click="activeTab = 'favorites'"
                    :class="activeTab === 'favorites' ? 'bg-red-500 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                    class="px-4 py-2 rounded-xl font-medium text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-heart mr-2"></i> Favoriler
            </button>
            <button @click="activeTab = 'watchlist'"
                    :class="activeTab === 'watchlist' ? 'bg-purple-500 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                    class="px-4 py-2 rounded-xl font-medium text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-bookmark mr-2"></i> İzlenecekler
            </button>
            <button @click="activeTab = 'following'"
                    :class="activeTab === 'following' ? 'bg-pink-500 text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                    class="px-4 py-2 rounded-xl font-medium text-sm whitespace-nowrap transition-colors">
                <i class="fas fa-users mr-2"></i> Takip Ettikleri
            </button>
        </div>

        {{-- Son İzlenenler --}}
        <div x-show="activeTab === 'recent'" x-transition>
            @if($recentMovies->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    @foreach($recentMovies as $movie)
                        <div class="group relative">
                            @if($movie->poster_path)
                                <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                    alt="{{ $movie->title }}"
                                    class="w-full aspect-[2/3] object-cover rounded-xl shadow-lg group-hover:ring-2 group-hover:ring-indigo-500 transition-all">
                            @else
                                <div class="w-full aspect-[2/3] bg-slate-800 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-film text-slate-700 text-2xl"></i>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                <div>
                                    <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                                    @if($movie->watched_at)
                                        <p class="text-slate-400 text-xs">{{ $movie->watched_at->diffForHumans() }}</p>
                                    @endif
                                    @if($movie->personal_rating)
                                        <div class="flex items-center gap-1 text-yellow-400 text-xs">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $movie->personal_rating ? '' : 'opacity-30' }}"></i>
                                            @endfor
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-slate-900/50 rounded-xl border border-slate-800">
                    <i class="fas fa-clock text-3xl text-slate-700 mb-3"></i>
                    <p class="text-slate-500">Henüz izlenen film yok</p>
                    @if($isOwnProfile)
                        <a href="{{ route('movies.create') }}"
                            class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-indigo-500 text-white text-sm font-semibold hover:bg-indigo-600 transition-colors">
                            <i class="fas fa-plus-circle"></i>
                            İlk filmini ekle
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Favoriler --}}
        <div x-show="activeTab === 'favorites'" x-transition x-cloak>
            @if($favoriteMovies->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    @foreach($favoriteMovies as $movie)
                        <div class="group relative">
                            @if($movie->poster_path)
                                <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                    alt="{{ $movie->title }}"
                                    class="w-full aspect-[2/3] object-cover rounded-xl shadow-lg group-hover:ring-2 group-hover:ring-red-500 transition-all">
                            @else
                                <div class="w-full aspect-[2/3] bg-slate-800 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-film text-slate-700 text-2xl"></i>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                <div>
                                    <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                                    <div class="flex items-center gap-1 text-yellow-400 text-xs">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $movie->personal_rating ? '' : 'opacity-30' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-slate-900/50 rounded-xl border border-slate-800">
                    <i class="fas fa-heart text-3xl text-slate-700 mb-3"></i>
                    <p class="text-slate-500">Henüz favori film yok</p>
                    @if($isOwnProfile)
                        <p class="text-xs text-slate-600 mt-2">Bir filmi 4+ puanlayınca burada görünecek.</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- İzlenecekler (Watchlist) --}}
        <div x-show="activeTab === 'watchlist'" x-transition x-cloak>
            @if($watchlistMovies->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-4">
                    @foreach($watchlistMovies as $movie)
                        <div class="group relative">
                            @if($movie->poster_path)
                                <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                    alt="{{ $movie->title }}"
                                    class="w-full aspect-[2/3] object-cover rounded-xl shadow-lg group-hover:ring-2 group-hover:ring-purple-500 transition-all">
                            @else
                                <div class="w-full aspect-[2/3] bg-slate-800 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-film text-slate-700 text-2xl"></i>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent rounded-xl opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-3">
                                <div>
                                    <p class="text-white text-sm font-bold truncate">{{ $movie->title }}</p>
                                    <p class="text-slate-400 text-xs">{{ $movie->release_date?->format('Y') }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-slate-900/50 rounded-xl border border-slate-800">
                    <i class="fas fa-bookmark text-3xl text-slate-700 mb-3"></i>
                    <p class="text-slate-500">İzlenecek listesi boş</p>
                    @if($isOwnProfile)
                        <a href="{{ route('movies.create') }}"
                            class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-xl bg-purple-500 text-white text-sm font-semibold hover:bg-purple-600 transition-colors">
                            <i class="fas fa-magic"></i>
                            Watchlist'e film ekle
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Takip Ettikleri --}}
        <div x-show="activeTab === 'following'" x-transition x-cloak>
            @if($followingUsers->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach($followingUsers as $followedUser)
                        <a href="{{ route('users.show', $followedUser) }}"
                           class="flex items-center gap-3 p-4 bg-slate-800/50 hover:bg-slate-800 rounded-xl transition-colors">
                            <img src="{{ $followedUser->avatar_url }}"
                                 alt="{{ $followedUser->name }}"
                                 class="w-12 h-12 rounded-full object-cover">
                            <div class="min-w-0">
                                <p class="text-white font-medium truncate">{{ $followedUser->name }}</p>
                                <p class="text-slate-500 text-xs">{{ $followedUser->movies_count ?? 0 }} film</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-slate-900/50 rounded-xl border border-slate-800">
                    <i class="fas fa-users text-3xl text-slate-700 mb-3"></i>
                    <p class="text-slate-500">Henüz kimseyi takip etmiyor</p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection
