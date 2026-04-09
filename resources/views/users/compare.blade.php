@extends('layouts.app')

@section('title', $user->name . ' ile Film Uyumu')

@section('content')
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }
    </style>

<div class="container mx-auto"
     x-data="{
        activeTab: 'analysis',
        genreModalOpen: false,
        selectedGenre: null,
        genreMovies: [],
        genreLoading: false,
        genreError: null,
        async openGenreModal(genreName) {
            this.selectedGenre = genreName;
            this.genreModalOpen = true;
            document.body.classList.add('overflow-hidden');
            this.genreLoading = true;
            this.genreError = null;
            this.genreMovies = [];

            try {
                const response = await fetch(`{{ route('users.compare', $user) }}?genre=${encodeURIComponent(genreName)}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const payload = await response.json();

                if (!response.ok) {
                    this.genreError = payload.message || 'İçerikler yüklenemedi.';
                    return;
                }

                this.genreMovies = payload.movies || [];
            } catch (e) {
                this.genreError = 'Bağlantı hatası oluştu. Tekrar deneyin.';
            } finally {
                this.genreLoading = false;
            }
        },
        closeGenreModal() {
            this.genreModalOpen = false;
            document.body.classList.remove('overflow-hidden');
        }
     }">

    {{-- ═══════════════════════════════════════════════════════════
         HERO BÖLÜMÜ — İki profil + Genel Uyum Skoru
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-8">
        <a href="{{ route('users.show', $user) }}" class="text-indigo-400 hover:text-indigo-300 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Profile Dön
        </a>

        <div class="flex items-center justify-center gap-6 mb-8">
            <div class="text-center">
                <div class="relative w-20 h-20 mx-auto">
                    <img src="{{ auth()->user()->avatar_url }}"
                         alt="{{ auth()->user()->name }}"
                         class="w-full h-full rounded-full object-cover shadow-lg ring-4 ring-indigo-500/50">
                    <div class="absolute bottom-0 right-0 bg-indigo-500 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-900">
                        <i class="fas fa-user text-white text-[10px]"></i>
                    </div>
                </div>
                <p class="mt-2 font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="text-sm text-slate-400">{{ $stats['my_total'] }} film</p>
            </div>

            {{-- Uyum Skoru — Animasyonlu Ring --}}
            <div class="text-center">
                @php
                    $scoreColor = match(true) {
                        $stats['similarity'] >= 70 => 'text-emerald-400',
                        $stats['similarity'] >= 40 => 'text-indigo-400',
                        default => 'text-orange-400',
                    };
                    $scoreLabel = match(true) {
                        $stats['similarity'] >= 70 => 'Mükemmel',
                        $stats['similarity'] >= 40 => 'İyi',
                        default => 'Keşfet',
                    };
                @endphp
                <div class="relative w-20 h-20 mx-auto" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="40" cy="40" r="34"
                                stroke="currentColor" stroke-width="6" fill="transparent"
                                class="text-slate-700/50"/>
                        <circle cx="40" cy="40" r="34"
                                stroke="url(#score-gradient)" stroke-width="6" fill="transparent"
                                stroke-dasharray="214"
                                :stroke-dashoffset="shown ? {{ 214 - (214 * $stats['similarity'] / 100) }} : 214"
                                stroke-linecap="round"
                                class="transition-all duration-[2000ms] ease-out"/>
                        <defs>
                            <linearGradient id="score-gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                @if($stats['similarity'] >= 70)
                                    <stop offset="0%" stop-color="#34d399"/>
                                    <stop offset="100%" stop-color="#10b981"/>
                                @elseif($stats['similarity'] >= 40)
                                    <stop offset="0%" stop-color="#818cf8"/>
                                    <stop offset="100%" stop-color="#c084fc"/>
                                @else
                                    <stop offset="0%" stop-color="#f97316"/>
                                    <stop offset="100%" stop-color="#ef4444"/>
                                @endif
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <p class="text-2xl font-black text-white mt-2">%{{ $stats['similarity'] }}</p>
                <p class="text-[10px] font-bold uppercase tracking-widest {{ $scoreColor }}">{{ $scoreLabel }}</p>
                <p class="text-[10px] text-slate-500 mt-1">6 Boyutlu Uyum</p>
                
                {{-- Güven Skoru --}}
                @if(isset($stats['confidence']))
                    @php
                        $confLevel = $stats['confidence']['level'];
                        $confColor = match($confLevel) {
                            'high' => 'text-emerald-400 bg-emerald-500/20 border-emerald-500/30',
                            'medium' => 'text-amber-400 bg-amber-500/20 border-amber-500/30',
                            default => 'text-red-400 bg-red-500/20 border-red-500/30',
                        };
                        $confIcon = match($confLevel) {
                            'high' => 'fa-check-circle',
                            'medium' => 'fa-info-circle',
                            default => 'fa-exclamation-triangle',
                        };
                    @endphp
                    <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full border text-[9px] {{ $confColor }}">
                        <i class="fas {{ $confIcon }}"></i>
                        <span>{{ $stats['confidence']['label'] }}</span>
                    </div>
                @endif
            </div>

            <div class="text-center">
                <div class="relative w-20 h-20 mx-auto">
                    <img src="{{ $user->avatar_url }}"
                         alt="{{ $user->name }}"
                         class="w-full h-full rounded-full object-cover shadow-lg ring-4 ring-purple-500/50">
                    <div class="absolute bottom-0 right-0 bg-purple-500 w-6 h-6 rounded-full flex items-center justify-center border-2 border-slate-900">
                        <i class="fas fa-user text-white text-[10px]"></i>
                    </div>
                </div>
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

    {{-- Tür İçerik Modalı (Teleport to body to fix z-index issues) --}}
    <template x-teleport="body">
        <div x-show="genreModalOpen"
             style="display:none; z-index: 9999;"
             class="fixed inset-0 flex items-center justify-center p-4 sm:p-6"
             role="dialog"
             aria-modal="true"
             aria-labelledby="modal-title">
            
            {{-- Backdrop --}}
        <div x-show="genreModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"
             @click="closeGenreModal()"></div>

        {{-- Modal Panel --}}
        <div x-show="genreModalOpen"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @keydown.escape.window="closeGenreModal()"
             class="relative w-full max-w-5xl max-h-[90vh] bg-slate-900 border border-slate-700/50 rounded-2xl sm:rounded-3xl shadow-2xl flex flex-col overflow-hidden ring-1 ring-white/10">
            
            {{-- Header --}}
            <div class="flex-none bg-slate-800/80 border-b border-slate-700/50 px-6 py-5 flex items-center justify-between backdrop-blur-md">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center border border-indigo-500/30">
                        <i class="fas fa-masks-theater text-indigo-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 id="modal-title" class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                            <span x-text="selectedGenre"></span> 
                        </h3>
                        <p class="text-sm text-slate-400 mt-0.5">
                            <template x-if="!genreLoading && !genreError && genreMovies.length > 0">
                                <span><strong class="text-indigo-400" x-text="genreMovies.length"></strong> ortak film bulundu</span>
                            </template>
                            <template x-if="genreLoading">
                                <span>Filmler yükleniyor...</span>
                            </template>
                            <template x-if="!genreLoading && !genreError && genreMovies.length === 0">
                                <span>Ortak film bulunamadı.</span>
                            </template>
                        </p>
                    </div>
                </div>
                <button type="button" @click="closeGenreModal()" class="w-10 h-10 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition-colors border border-slate-700">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            {{-- Content / Scrollable Area --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar p-6">
                <!-- Loading State -->
                <template x-if="genreLoading">
                    <div class="flex flex-col items-center justify-center py-24">
                        <div class="relative w-16 h-16 mb-6">
                            <div class="absolute inset-0 rounded-full border-t-2 border-indigo-500 animate-spin"></div>
                            <div class="absolute inset-2 rounded-full border-r-2 border-purple-500 animate-spin" style="animation-direction: reverse; animation-duration: 1.5s;"></div>
                            <i class="fas fa-film absolute inset-0 flex items-center justify-center text-slate-500 text-xl"></i>
                        </div>
                    </div>
                </template>

                <!-- Error State -->
                <template x-if="genreError && !genreLoading">
                    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
                        <div class="w-20 h-20 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mb-5">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">Eyvah, bir sorun var!</h4>
                        <p class="text-slate-400 mb-6" x-text="genreError"></p>
                        <button @click="openGenreModal(selectedGenre)" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl transition-colors font-medium border border-slate-600 hover:border-slate-500 flex items-center gap-2">
                            <i class="fas fa-rotate-right"></i> Tekrar Dene
                        </button>
                    </div>
                </template>

                <!-- Empty State -->
                <template x-if="!genreLoading && !genreError && genreMovies.length === 0">
                    <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
                        <div class="w-24 h-24 rounded-full bg-slate-800/80 border border-slate-700 flex items-center justify-center mb-6">
                            <i class="fas fa-ghost text-slate-500 text-4xl hover:text-slate-400 transition-colors"></i>
                        </div>
                        <h4 class="text-xl font-bold text-white mb-2">Buralar Çok Issız</h4>
                        <p class="text-slate-400 max-w-sm mx-auto">Görünüşe göre bu türde henüz ortak izlediğiniz bir film yok.</p>
                    </div>
                </template>

                <!-- Movies Grid -->
                <template x-if="!genreLoading && !genreError && genreMovies.length > 0">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                        <template x-for="movie in genreMovies" :key="movie.id">
                            <a :href="movie.url" class="group block relative rounded-xl sm:rounded-2xl overflow-hidden bg-slate-800 shadow-lg ring-1 ring-slate-700 hover:ring-indigo-500/60 transition-all duration-300">
                                <div class="aspect-[2/3] w-full relative bg-slate-900 overflow-hidden">
                                    <template x-if="movie.poster_path">
                                        <img :src="'https://image.tmdb.org/t/p/w300' + movie.poster_path"
                                             :alt="movie.title"
                                             class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                             loading="lazy">
                                    </template>
                                    <template x-if="!movie.poster_path">
                                        <div class="absolute inset-0 w-full h-full flex flex-col items-center justify-center text-slate-600 bg-slate-800 border-b border-slate-700/50">
                                            <i class="fas fa-film text-4xl mb-3 opacity-40"></i>
                                            <span class="text-[10px] font-bold uppercase tracking-widest opacity-60">Afiş Yok</span>
                                        </div>
                                    </template>
                                    
                                    <!-- Gradient Overlay -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent opacity-80 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <!-- Content -->
                                    <div class="absolute inset-0 p-3 sm:p-4 flex flex-col justify-end">
                                        <div class="transform translate-y-3 group-hover:translate-y-0 transition-transform duration-300">
                                            <h4 class="text-sm sm:text-base font-bold text-white line-clamp-2 leading-tight drop-shadow-lg mb-1.5" x-text="movie.title"></h4>
                                            
                                            <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
                                                <template x-if="movie.release_year">
                                                    <span class="inline-flex items-center text-[10px] font-bold text-slate-300 bg-slate-800/80 px-2 py-0.5 rounded-md backdrop-blur-sm border border-slate-600/50">
                                                        <span x-text="movie.release_year"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </template>
                    </div>
                </template>
            </div>
            
        </div>
        </div>
    </template>

    {{-- ═══════════════════════════════════════════════════════════
         TAB NAVİGASYON
         ═══════════════════════════════════════════════════════════ --}}
    <div class="space-y-6">
        <div class="flex justify-center gap-2 border-b border-slate-700 pb-4 flex-wrap">
            <button @click="activeTab = 'analysis'"
                    :class="activeTab === 'analysis' ? 'bg-gradient-to-r from-indigo-500/20 to-purple-500/20 text-indigo-400 border-indigo-500/50' : 'bg-slate-800/50 text-slate-400 border-slate-700 hover:border-slate-600'"
                    class="px-6 py-2.5 rounded-xl font-medium border transition-all">
                <i class="fas fa-chart-radar mr-2"></i>
                Analiz
            </button>
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

        {{-- ═══════════════════════════════════════════════════════════
             ANALİZ TABI — Spotify Wrapped Tarzı Kartlar
             ═══════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'analysis'" x-transition>

            {{-- Boyut Skorları Özet --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
                @php
                    $dimensions = [
                        ['key' => 'movies', 'label' => 'Ortak Film', 'icon' => 'fa-film', 'color' => 'emerald'],
                        ['key' => 'genres', 'label' => 'Tür Uyumu', 'icon' => 'fa-masks-theater', 'color' => 'indigo'],
                        ['key' => 'directors', 'label' => 'Yönetmen', 'icon' => 'fa-bullhorn', 'color' => 'purple'],
                        ['key' => 'cast', 'label' => 'Oyuncu', 'icon' => 'fa-users', 'color' => 'pink'],
                        ['key' => 'decades', 'label' => 'Dönem', 'icon' => 'fa-clock-rotate-left', 'color' => 'amber'],
                        ['key' => 'ratings', 'label' => 'Puan', 'icon' => 'fa-star', 'color' => 'yellow'],
                    ];
                @endphp

                @foreach($dimensions as $dim)
                    @php
                        $score = $analysis['dimensions'][$dim['key']]['score'];
                        $colorMap = [
                            'emerald' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'border' => 'hover:border-emerald-500/30'],
                            'indigo'  => ['bg' => 'bg-indigo-500/10',  'text' => 'text-indigo-400',  'border' => 'hover:border-indigo-500/30'],
                            'purple'  => ['bg' => 'bg-purple-500/10',  'text' => 'text-purple-400',  'border' => 'hover:border-purple-500/30'],
                            'pink'    => ['bg' => 'bg-pink-500/10',    'text' => 'text-pink-400',    'border' => 'hover:border-pink-500/30'],
                            'amber'   => ['bg' => 'bg-amber-500/10',   'text' => 'text-amber-400',   'border' => 'hover:border-amber-500/30'],
                            'yellow'  => ['bg' => 'bg-yellow-500/10',  'text' => 'text-yellow-400',  'border' => 'hover:border-yellow-500/30'],
                        ];
                        $c = $colorMap[$dim['color']];
                    @endphp
                    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 text-center {{ $c['border'] }} transition-all group">
                        <div class="{{ $c['bg'] }} w-10 h-10 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                            <i class="fas {{ $dim['icon'] }} {{ $c['text'] }}"></i>
                        </div>
                        <p class="text-2xl font-black text-white">%{{ $score }}</p>
                        <p class="text-xs text-slate-500 font-medium">{{ $dim['label'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Detay Kartları --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- TÜR UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-indigo-500/10 w-10 h-10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-masks-theater text-indigo-400"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold">Tür Uyumu</h3>
                            <p class="text-xs text-slate-500">İzleme zevklerinizdeki ortak türler</p>
                        </div>
                        <span class="ml-auto text-2xl font-black text-indigo-400">%{{ $analysis['dimensions']['genres']['score'] }}</span>
                    </div>

                    @if(!empty($analysis['dimensions']['genres']['top_common']))
                        @php
                            $genreStyles = [
                                'Aksiyon' => ['color' => 'text-orange-500', 'border' => 'group-hover:border-orange-500/50', 'icon' => 'fa-fire'],
                                'Macera' => ['color' => 'text-emerald-500', 'border' => 'group-hover:border-emerald-500/50', 'icon' => 'fa-mountain-sun'],
                                'Animasyon' => ['color' => 'text-pink-500', 'border' => 'group-hover:border-pink-500/50', 'icon' => 'fa-smile-beam'],
                                'Komedi' => ['color' => 'text-yellow-400', 'border' => 'group-hover:border-yellow-400/50', 'icon' => 'fa-masks-theater'],
                                'Suç' => ['color' => 'text-slate-400', 'border' => 'group-hover:border-slate-400/50', 'icon' => 'fa-handcuffs'],
                                'Belgesel' => ['color' => 'text-stone-400', 'border' => 'group-hover:border-stone-400/50', 'icon' => 'fa-video'],
                                'Dram' => ['color' => 'text-blue-500', 'border' => 'group-hover:border-blue-500/50', 'icon' => 'fa-face-sad-tear'],
                                'Aile' => ['color' => 'text-sky-400', 'border' => 'group-hover:border-sky-400/50', 'icon' => 'fa-house-chimney'],
                                'Fantastik' => ['color' => 'text-purple-400', 'border' => 'group-hover:border-purple-400/50', 'icon' => 'fa-wand-magic-sparkles'],
                                'Tarih' => ['color' => 'text-amber-600', 'border' => 'group-hover:border-amber-600/50', 'icon' => 'fa-scroll'],
                                'Korku' => ['color' => 'text-red-600', 'border' => 'group-hover:border-red-600/50', 'icon' => 'fa-ghost'],
                                'Müzik' => ['color' => 'text-fuchsia-400', 'border' => 'group-hover:border-fuchsia-400/50', 'icon' => 'fa-music'],
                                'Gizem' => ['color' => 'text-indigo-400', 'border' => 'group-hover:border-indigo-400/50', 'icon' => 'fa-magnifying-glass'],
                                'Romantik' => ['color' => 'text-rose-400', 'border' => 'group-hover:border-rose-400/50', 'icon' => 'fa-heart'],
                                'Bilim Kurgu' => ['color' => 'text-cyan-400', 'border' => 'group-hover:border-cyan-400/50', 'icon' => 'fa-robot'],
                                'TV Filmi' => ['color' => 'text-slate-500', 'border' => 'group-hover:border-slate-500/50', 'icon' => 'fa-tv'],
                                'Gerilim' => ['color' => 'text-red-500', 'border' => 'group-hover:border-red-500/50', 'icon' => 'fa-bolt'],
                                'Savaş' => ['color' => 'text-stone-500', 'border' => 'group-hover:border-stone-500/50', 'icon' => 'fa-jet-fighter'],
                                'Vahşi Batı' => ['color' => 'text-amber-700', 'border' => 'group-hover:border-amber-700/50', 'icon' => 'fa-hat-cowboy'],
                            ];
                        @endphp
                        <div class="mt-3 border-t border-slate-800/50 pt-3">
                            <div class="max-h-[310px] overflow-y-auto pr-1 pb-1 custom-scrollbar">
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach($analysis['dimensions']['genres']['top_common'] as $index => $genreObj)
                                    @php
                                        $style = $genreStyles[$genreObj['name']] ?? ['color' => 'text-indigo-400', 'border' => 'group-hover:border-indigo-400/50', 'icon' => 'fa-film'];
                                    @endphp
                                    <button type="button"
                                            @click="openGenreModal('{{ addslashes($genreObj['name']) }}')"
                                            class="w-full rounded-xl p-3 text-center transition-all duration-200 group bg-slate-800/30 hover:bg-slate-800/60 border border-transparent">
                                        <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-slate-900/80 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <i class="fas {{ $style['icon'] }} {{ $style['color'] }} text-lg"></i>
                                        </div>
                                        <p class="text-white text-xs font-semibold truncate">{{ $genreObj['name'] }}</p>
                                        <p class="text-slate-500 text-[10px] mt-0.5">{{ $genreObj['count'] }} film</p>
                                    </button>
                                @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-ghost text-slate-700 text-3xl mb-2"></i>
                            <p class="text-slate-500 text-sm">Ortak tür bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- YÖNETMEN UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-purple-500/10 w-10 h-10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-bullhorn text-purple-400"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold">Yönetmen Uyumu</h3>
                            <p class="text-xs text-slate-500">{{ $analysis['dimensions']['directors']['common_count'] }} ortak yönetmen</p>
                        </div>
                        <span class="ml-auto text-2xl font-black text-purple-400">%{{ $analysis['dimensions']['directors']['score'] }}</span>
                    </div>

                    @if(!empty($analysis['dimensions']['directors']['top_common']))
                        <div class="mt-3 border-t border-slate-800/50 pt-3" x-data="{ expanded: false }">
                            @php $totalDirectors = count($analysis['dimensions']['directors']['top_common']); @endphp
                            
                            <div :class="expanded ? 'max-h-[310px] overflow-y-auto custom-scrollbar' : ''" class="pr-1 pb-1">
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach($analysis['dimensions']['directors']['top_common'] as $index => $director)
                                    <div class="relative w-full overflow-hidden rounded-xl aspect-[2/3] bg-slate-900 border border-slate-800 group shadow-lg cursor-default"
                                         x-show="expanded || {{ $index }} < 6"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100">
                                        @if(!empty($director['profile_path']))
                                            <img src="https://image.tmdb.org/t/p/w185{{ $director['profile_path'] }}"
                                                 alt="{{ $director['name'] }}"
                                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        @else
                                            <div class="absolute inset-0 w-full h-full flex items-center justify-center bg-slate-800">
                                                <i class="fas fa-video text-slate-600 text-3xl"></i>
                                            </div>
                                        @endif

                                        {{-- Karartma Gradienti --}}
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

                                        {{-- Hover Tint --}}
                                        <div class="absolute inset-0 bg-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                                        {{-- Metin Alanı --}}
                                        <div class="absolute bottom-0 inset-x-0 p-2 text-center transform translate-y-0.5 group-hover:-translate-y-0.5 transition-transform duration-300">
                                            <span class="text-xs text-white font-bold block truncate drop-shadow-md" title="{{ $director['name'] }}">{{ $director['name'] }}</span>
                                            <span class="text-[9px] text-purple-400 font-bold uppercase tracking-wider block mt-0.5 drop-shadow-md">{{ $director['common_films'] }} ORTAK</span>
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                            
                            @if($totalDirectors > 6)
                                <button @click="expanded = !expanded" 
                                        class="w-full mt-3 py-2 text-xs font-medium text-purple-400 hover:text-purple-300 bg-purple-500/10 hover:bg-purple-500/20 rounded-lg transition-colors flex items-center justify-center gap-2">
                                    <span x-text="expanded ? 'Daha az göster' : '+{{ $totalDirectors - 6 }} kişi daha'"></span>
                                    <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="m-auto py-8 text-center opacity-75">
                            <i class="fas fa-ghost text-slate-700 text-4xl mb-3"></i>
                            <p class="text-slate-500 text-sm">Ortak yönetmen bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- OYUNCU UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-pink-500/10 w-10 h-10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-users text-pink-400"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold">Oyuncu Uyumu</h3>
                            <p class="text-xs text-slate-500">{{ $analysis['dimensions']['cast']['common_count'] }} ortak oyuncu</p>
                        </div>
                        <span class="ml-auto text-2xl font-black text-pink-400">%{{ $analysis['dimensions']['cast']['score'] }}</span>
                    </div>

                    @if(!empty($analysis['dimensions']['cast']['top_common']))
                        <div class="mt-3 border-t border-slate-800/50 pt-3" x-data="{ expanded: false }">
                            @php $totalActors = count($analysis['dimensions']['cast']['top_common']); @endphp
                            
                            <div :class="expanded ? 'max-h-[310px] overflow-y-auto custom-scrollbar' : ''" class="pr-1 pb-1">
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach($analysis['dimensions']['cast']['top_common'] as $index => $actor)
                                    <div class="relative w-full overflow-hidden rounded-xl aspect-[2/3] bg-slate-900 border border-slate-800 group shadow-lg cursor-default"
                                         x-show="expanded || {{ $index }} < 6"
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100">
                                        @if(!empty($actor['profile_path']))
                                            <img src="https://image.tmdb.org/t/p/w185{{ $actor['profile_path'] }}"
                                                 alt="{{ $actor['name'] }}"
                                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                        @else
                                            <div class="absolute inset-0 w-full h-full flex items-center justify-center bg-slate-800">
                                                <i class="fas fa-user-tie text-slate-600 text-3xl"></i>
                                            </div>
                                        @endif

                                        {{-- Karartma Gradienti --}}
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>

                                        {{-- Hover Tint --}}
                                        <div class="absolute inset-0 bg-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

                                        {{-- Metin Alanı --}}
                                        <div class="absolute bottom-0 inset-x-0 p-2 text-center transform translate-y-0.5 group-hover:-translate-y-0.5 transition-transform duration-300">
                                            <span class="text-xs text-white font-bold block truncate drop-shadow-md" title="{{ $actor['name'] }}">{{ $actor['name'] }}</span>
                                            <span class="text-[9px] text-pink-400 font-bold uppercase tracking-wider block mt-0.5 drop-shadow-md">{{ $actor['common_films'] }} ORTAK</span>
                                        </div>
                                    </div>
                                @endforeach
                                </div>
                            </div>
                            
                            @if($totalActors > 6)
                                <button @click="expanded = !expanded" 
                                        class="w-full mt-3 py-2 text-xs font-medium text-pink-400 hover:text-pink-300 bg-pink-500/10 hover:bg-pink-500/20 rounded-lg transition-colors flex items-center justify-center gap-2">
                                    <span x-text="expanded ? 'Daha az göster' : '+{{ $totalActors - 6 }} kişi daha'"></span>
                                    <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="m-auto py-8 text-center opacity-75">
                            <i class="fas fa-ghost text-slate-700 text-4xl mb-3"></i>
                            <p class="text-slate-500 text-sm">Ortak oyuncu bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- DÖNEM UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-amber-500/10 w-10 h-10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock-rotate-left text-amber-400"></i>
                        </div>
                        <div>
                            <h3 class="text-white font-bold">Dönem Uyumu</h3>
                            <p class="text-xs text-slate-500">Hangi dönemi tercih ediyorsunuz?</p>
                        </div>
                        <span class="ml-auto text-2xl font-black text-amber-400">%{{ $analysis['dimensions']['decades']['score'] }}</span>
                    </div>

                    @if(!empty($analysis['dimensions']['decades']['ref_decades']) || !empty($analysis['dimensions']['decades']['other_decades']))
                        @php
                            $allDecades = array_unique(array_merge(
                                array_keys($analysis['dimensions']['decades']['ref_decades'] ?? []),
                                array_keys($analysis['dimensions']['decades']['other_decades'] ?? [])
                            ));
                            sort($allDecades);
                            $globalMax = max(
                                !empty($analysis['dimensions']['decades']['ref_decades']) ? max($analysis['dimensions']['decades']['ref_decades']) : 1,
                                !empty($analysis['dimensions']['decades']['other_decades']) ? max($analysis['dimensions']['decades']['other_decades']) : 1
                            );
                        @endphp
                        <div class="mt-3 border-t border-slate-800/50 pt-3">
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($allDecades as $decade)
                                    @php
                                        $myCount = $analysis['dimensions']['decades']['ref_decades'][$decade] ?? 0;
                                        $theirCount = $analysis['dimensions']['decades']['other_decades'][$decade] ?? 0;
                                        $total = $myCount + $theirCount;
                                    @endphp
                                    <div class="bg-slate-800/50 hover:bg-slate-800 border border-slate-700/50 hover:border-slate-600 rounded-xl p-3 text-center transition-all duration-200 group cursor-default">
                                        <div class="w-10 h-10 mx-auto mb-2 rounded-lg bg-slate-900/80 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <i class="fas fa-calendar text-amber-400 text-lg"></i>
                                        </div>
                                        <p class="text-white text-xs font-semibold">{{ $decade }}</p>
                                        <div class="flex items-center justify-center gap-1 mt-1">
                                            <span class="text-[10px] text-indigo-400 font-bold">{{ $myCount }}</span>
                                            <span class="text-[10px] text-slate-600">/</span>
                                            <span class="text-[10px] text-purple-400 font-bold">{{ $theirCount }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="flex items-center justify-center gap-6 mt-4 pt-3 border-t border-slate-800/50">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded bg-indigo-500"></span>
                                    <span class="text-xs text-slate-300 font-medium">Sen</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded bg-purple-500"></span>
                                    <span class="text-xs text-slate-300 font-medium">{{ $user->name }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="m-auto py-8 text-center opacity-75">
                            <i class="fas fa-ghost text-slate-700 text-4xl mb-3"></i>
                            <p class="text-slate-500 text-sm">Dönem verisi bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- PUAN EĞİLİMİ KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 lg:col-span-2">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="bg-yellow-500/10 w-10 h-10 rounded-xl flex items-center justify-center">
                            <i class="fas fa-star text-yellow-400"></i>
                        </div>
                        <h3 class="text-white font-bold">Puan Eğilimi</h3>
                        <span class="ml-auto text-2xl font-black text-yellow-400">%{{ $analysis['dimensions']['ratings']['score'] }}</span>
                    </div>

                        @if(!empty($analysis['dimensions']['ratings']['insufficient']))
                            <div class="text-center py-6">
                                <i class="fas fa-exclamation-triangle text-yellow-500/50 text-3xl mb-2"></i>
                                <p class="text-slate-400 text-sm">{{ $analysis['dimensions']['ratings']['insufficient_reason'] ?? 'Karşılaştırma için ortak puanlanmış film gerekli.' }}</p>
                            </div>
                        @else
                        <div class="grid grid-cols-3 gap-4 items-center">
                            <div class="text-center">
                                <p class="text-3xl font-black text-indigo-400">{{ $analysis['dimensions']['ratings']['my_avg'] }}</p>
                            </div>
                            
                            <div class="text-center">
                                <div class="w-14 h-14 mx-auto rounded-full bg-slate-800/80 flex items-center justify-center border-2 border-yellow-500/30">
                                    <p class="text-sm font-black {{ $analysis['dimensions']['ratings']['correlation'] >= 0.5 ? 'text-emerald-400' : ($analysis['dimensions']['ratings']['correlation'] >= 0 ? 'text-amber-400' : 'text-red-400') }}">
                                        {{ $analysis['dimensions']['ratings']['correlation'] >= 0 ? '+' : '' }}{{ $analysis['dimensions']['ratings']['correlation'] }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <p class="text-3xl font-black text-purple-400">{{ $analysis['dimensions']['ratings']['their_avg'] }}</p>
                            </div>
                        </div>
                        
                        <p class="text-center text-xs text-slate-500 mt-3">
                            <span class="text-yellow-400 font-bold">{{ $analysis['dimensions']['ratings']['common_rated'] }}</span> ortak film
                        </p>
                        @endif
                </div>

            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             ORTAK FİLMLER TABI
             ═══════════════════════════════════════════════════════════ --}}
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

        {{-- ═══════════════════════════════════════════════════════════
             SADECE BEN TABI
             ═══════════════════════════════════════════════════════════ --}}
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

        {{-- ═══════════════════════════════════════════════════════════
             SADECE O TABI
             ═══════════════════════════════════════════════════════════ --}}
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
    window.location.href = '/search?query=' + encodeURIComponent(title);
}
</script>
@endsection
