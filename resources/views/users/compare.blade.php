@extends('layouts.app')

@section('title', $user->name . ' ile Film Uyumu')

@section('content')
<div class="container mx-auto" x-data="{ activeTab: 'analysis' }">

    {{-- ═══════════════════════════════════════════════════════════
         HERO BÖLÜMÜ — İki profil + Genel Uyum Skoru
         ═══════════════════════════════════════════════════════════ --}}
    <div class="mb-8">
        <a href="{{ route('users.show', $user) }}" class="text-indigo-400 hover:text-indigo-300 mb-4 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Profile Dön
        </a>

        <div class="flex items-center justify-center gap-8 mb-8">
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
                <div class="relative w-28 h-28 mx-auto" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="56" cy="56" r="48"
                                stroke="currentColor" stroke-width="8" fill="transparent"
                                class="text-slate-700/50"/>
                        <circle cx="56" cy="56" r="48"
                                stroke="url(#score-gradient)" stroke-width="8" fill="transparent"
                                stroke-dasharray="301"
                                :stroke-dashoffset="shown ? {{ 301 - (301 * $stats['similarity'] / 100) }} : 301"
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
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-black text-white">%{{ $stats['similarity'] }}</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest
                            @if($stats['similarity'] >= 70) text-emerald-400
                            @elseif($stats['similarity'] >= 40) text-indigo-400
                            @else text-orange-400 @endif
                        ">
                            @if($stats['similarity'] >= 70) Mükemmel
                            @elseif($stats['similarity'] >= 40) İyi
                            @else Keşfet @endif
                        </span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1 font-medium">6 Boyutlu Uyum</p>
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

                    @if(!empty($analysis['dimensions']['genres']['common']))
                        <div class="space-y-2">
                            @foreach($analysis['dimensions']['genres']['common'] as $genre => $count)
                                @php
                                    $maxCount = max(array_values($analysis['dimensions']['genres']['common']));
                                    $percent = $maxCount > 0 ? round(($count / $maxCount) * 100) : 0;
                                @endphp
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-slate-300 w-24 truncate">{{ $genre }}</span>
                                    <div class="flex-1 bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-full rounded-full transition-all duration-1000"
                                             style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-500 w-8 text-right">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-ghost text-slate-700 text-3xl mb-2"></i>
                            <p class="text-slate-500 text-sm">Ortak tür bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- YÖNETMEN UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col h-full">
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
                        <div class="grid grid-cols-3 gap-2 flex-1 mt-3">
                            @foreach($analysis['dimensions']['directors']['top_common'] as $director)
                                <div class="relative overflow-hidden rounded-xl aspect-[2/3] bg-slate-900 border border-slate-800 group shadow-lg cursor-default">
                                    @if(!empty($director['profile_path']))
                                        <img src="https://image.tmdb.org/t/p/w185{{ $director['profile_path'] }}" 
                                             alt="{{ $director['name'] }}" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-slate-800">
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
                                        <span class="text-[9px] text-purple-400 font-bold uppercase tracking-wider block mt-0.5 drop-shadow-md">{{ $director['total_films'] }} FILM</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="m-auto py-8 text-center opacity-75">
                            <i class="fas fa-ghost text-slate-700 text-4xl mb-3"></i>
                            <p class="text-slate-500 text-sm">Ortak yönetmen bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- OYUNCU UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col h-full">
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
                        <div class="grid grid-cols-3 gap-2 flex-1 mt-3">
                            @foreach($analysis['dimensions']['cast']['top_common'] as $actor)
                                <div class="relative overflow-hidden rounded-xl aspect-[2/3] bg-slate-900 border border-slate-800 group shadow-lg cursor-default">
                                    @if(!empty($actor['profile_path']))
                                        <img src="https://image.tmdb.org/t/p/w185{{ $actor['profile_path'] }}" 
                                             alt="{{ $actor['name'] }}" 
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-slate-800">
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
                                        <span class="text-[9px] text-pink-400 font-bold uppercase tracking-wider block mt-0.5 drop-shadow-md">{{ $actor['total_films'] }} FILM</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="m-auto py-8 text-center opacity-75">
                            <i class="fas fa-ghost text-slate-700 text-4xl mb-3"></i>
                            <p class="text-slate-500 text-sm">Ortak oyuncu bulunamadı.</p>
                        </div>
                    @endif
                </div>

                {{-- DÖNEM UYUMU KARTI --}}
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 flex flex-col h-full">
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

                    @if(!empty($analysis['dimensions']['decades']['my_decades']) || !empty($analysis['dimensions']['decades']['their_decades']))
                        @php
                            $allDecades = array_unique(array_merge(
                                array_keys($analysis['dimensions']['decades']['my_decades'] ?? []),
                                array_keys($analysis['dimensions']['decades']['their_decades'] ?? [])
                            ));
                            sort($allDecades);
                            $globalMax = max(
                                !empty($analysis['dimensions']['decades']['my_decades']) ? max($analysis['dimensions']['decades']['my_decades']) : 1,
                                !empty($analysis['dimensions']['decades']['their_decades']) ? max($analysis['dimensions']['decades']['their_decades']) : 1
                            );
                        @endphp
                        <div class="space-y-3 flex-1">
                            @foreach($allDecades as $decade)
                                @php
                                    $myCount = $analysis['dimensions']['decades']['my_decades'][$decade] ?? 0;
                                    $theirCount = $analysis['dimensions']['decades']['their_decades'][$decade] ?? 0;
                                    $myPercent = $globalMax > 0 ? round(($myCount / $globalMax) * 100) : 0;
                                    $theirPercent = $globalMax > 0 ? round(($theirCount / $globalMax) * 100) : 0;
                                @endphp
                                <div class="bg-slate-800/30 rounded-xl p-3 border border-slate-700/50">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs text-slate-300 font-bold tracking-wide">{{ $decade }}</span>
                                        <span class="text-[10px] bg-slate-900 px-2 py-0.5 rounded border border-slate-800 text-slate-400">
                                            <span class="text-indigo-400 font-bold">{{ $myCount }}</span> <span class="mx-0.5 opacity-50">/</span> <span class="text-purple-400 font-bold">{{ $theirCount }}</span>
                                        </span>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        {{-- Senin Barın --}}
                                        <div class="w-full bg-slate-700/50 rounded-full h-[6px] overflow-hidden relative" title="Sen: {{ $myCount }} film">
                                            <div class="bg-indigo-500 h-full rounded-full transition-all" style="width: {{ $myPercent }}%"></div>
                                        </div>
                                        {{-- Onun Barı --}}
                                        <div class="w-full bg-slate-700/50 rounded-full h-[6px] overflow-hidden relative" title="{{ $user->name }}: {{ $theirCount }} film">
                                            <div class="bg-purple-500 h-full rounded-full transition-all" style="width: {{ $theirPercent }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-center gap-6 mt-6 pt-4 border-t border-slate-800 text-xs text-slate-400">
                            <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-indigo-500"></span>Sen</span>
                            <span class="flex items-center gap-1.5"><span class="inline-block w-2.5 h-2.5 rounded-sm bg-purple-500"></span>{{ Str::limit($user->name, 10) }}</span>
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
                        <div>
                            <h3 class="text-white font-bold">Puan Eğilimi</h3>
                            <p class="text-xs text-slate-500">TMDB puan ortalamalarınızın karşılaştırması</p>
                        </div>
                        <span class="ml-auto text-2xl font-black text-yellow-400">%{{ $analysis['dimensions']['ratings']['score'] }}</span>
                    </div>

                        <div class="grid grid-cols-2 gap-6">
                            @if(!empty($analysis['dimensions']['ratings']['insufficient']))
                                <div class="col-span-2 text-center py-6">
                                    <i class="fas fa-exclamation-triangle text-yellow-500/50 text-3xl mb-2"></i>
                                    <p class="text-slate-400 text-sm">Karşılaştırma için iki tarafın da puanlı filmi olmalı.</p>
                                </div>
                            @else
                            {{-- Benim Puanım --}}
                            <div class="flex flex-col text-center">
                                <div class="bg-indigo-500/10 border border-indigo-500/20 rounded-2xl p-5 flex flex-col h-full">
                                    <p class="text-xs text-slate-500 mb-1 uppercase font-bold tracking-wider">Senin Ortalaman</p>
                                    <p class="text-4xl font-black text-indigo-400">{{ $analysis['dimensions']['ratings']['my_avg'] }}</p>
                                    <p class="text-xs text-slate-600 mt-1 mb-auto">/ 10 TMDB</p>
                                    
                                    <div class="mt-4 pt-4 border-t border-slate-800">
                                        <p class="text-xs text-slate-500 mb-1">Kişisel</p>
                                        @if($analysis['dimensions']['ratings']['my_personal'] > 0)
                                            <p class="text-xl font-bold text-indigo-300">{{ $analysis['dimensions']['ratings']['my_personal'] }} <span class="text-xs text-slate-600 font-normal">/ 5</span></p>
                                        @else
                                            <p class="text-sm font-medium text-slate-600">—</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Onun Puanı --}}
                            <div class="flex flex-col text-center">
                                <div class="bg-purple-500/10 border border-purple-500/20 rounded-2xl p-5 flex flex-col h-full">
                                    <p class="text-xs text-slate-500 mb-1 uppercase font-bold tracking-wider">{{ Str::limit($user->name, 10) }}</p>
                                    <p class="text-4xl font-black text-purple-400">{{ $analysis['dimensions']['ratings']['their_avg'] }}</p>
                                    <p class="text-xs text-slate-600 mt-1 mb-auto">/ 10 TMDB</p>
                                    
                                    <div class="mt-4 pt-4 border-t border-slate-800">
                                        <p class="text-xs text-slate-500 mb-1">Kişisel</p>
                                        @if($analysis['dimensions']['ratings']['their_personal'] > 0)
                                            <p class="text-xl font-bold text-purple-300">{{ $analysis['dimensions']['ratings']['their_personal'] }} <span class="text-xs text-slate-600 font-normal">/ 5</span></p>
                                        @else
                                            <p class="text-sm font-medium text-slate-600">—</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
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
