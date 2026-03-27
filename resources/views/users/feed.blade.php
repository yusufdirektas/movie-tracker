@extends('layouts.app')

@section('title', 'Aktivite Akışı')

@section('content')
<div class="container mx-auto max-w-3xl">

    {{-- BAŞLIK --}}
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight">
            <i class="fas fa-rss text-indigo-400 mr-3"></i>
            Aktivite <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Akışı</span>
        </h1>
        <p class="text-slate-500 mt-2">Takip ettiğin kişilerin son izledikleri</p>
    </div>

    @if($activities->isEmpty())
        <div class="text-center py-16 bg-slate-900/50 rounded-3xl border border-slate-800">
            <i class="fas fa-user-friends text-4xl text-slate-700 mb-4"></i>
            <p class="text-slate-500 mb-4">Henüz takip ettiğin kimse yok veya aktivite bulunmuyor.</p>
            <a href="{{ route('users.index') }}"
                class="inline-block px-6 py-3 bg-indigo-500 text-white font-bold rounded-xl hover:bg-indigo-600 transition-colors">
                <i class="fas fa-search mr-2"></i> Kullanıcı Keşfet
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($activities as $movie)
                <div data-testid="feed-activity-card" class="bg-slate-900 border border-slate-800 rounded-2xl p-4 hover:border-slate-700 transition-all">
                    <div class="flex gap-4">
                        {{-- Film Poster --}}
                        <div class="w-16 h-24 flex-shrink-0">
                            @if($movie->poster_path)
                                <img src="https://image.tmdb.org/t/p/w92{{ $movie->poster_path }}"
                                    alt="{{ $movie->title }}"
                                    class="w-full h-full object-cover rounded-lg shadow-md">
                            @else
                                <div class="w-full h-full bg-slate-800 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-film text-slate-700"></i>
                                </div>
                            @endif
                        </div>

                        {{-- İçerik --}}
                        <div class="flex-1 min-w-0">
                            {{-- Kullanıcı --}}
                            <div class="flex items-center gap-2 mb-2">
                                <a href="{{ route('users.show', $movie->user) }}"
                                    class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    {{ strtoupper(substr($movie->user->name, 0, 1)) }}
                                </a>
                                <a href="{{ route('users.show', $movie->user) }}"
                                    class="text-white font-bold hover:text-indigo-400 transition-colors">
                                    {{ $movie->user->name }}
                                </a>
                                <span class="text-slate-600 text-sm">izledi</span>
                            </div>

                            {{-- Film Bilgisi --}}
                            <h3 class="text-white font-bold truncate">{{ $movie->title }}</h3>
                            <div class="flex items-center gap-3 text-sm text-slate-500 mt-1">
                                @if($movie->release_date)
                                    <span>{{ \Carbon\Carbon::parse($movie->release_date)->format('Y') }}</span>
                                @endif
                                @if($movie->personal_rating)
                                    <span class="flex items-center gap-1 text-yellow-400">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star text-xs {{ $i <= $movie->personal_rating ? '' : 'opacity-30' }}"></i>
                                        @endfor
                                    </span>
                                @endif
                                @if($movie->watched_at)
                                    <span class="text-slate-600">
                                        {{ \Carbon\Carbon::parse($movie->watched_at)->diffForHumans() }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('users.show', $movie->user) }}"
                                   class="inline-flex items-center gap-2 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors">
                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                    {{ $movie->user->name }} profilini aç
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- SAYFALAMA --}}
        <div class="mt-8">
            {{ $activities->links() }}
        </div>
    @endif

</div>
@endsection
