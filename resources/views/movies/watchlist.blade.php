@extends('layouts.app')

@section('title', 'İzlenecek Filmlerim')

@section('content')
    <div class="container mx-auto">

        <div class="mb-12">
            <div class="flex flex-col md:flex-row justify-between items-end mb-6">
                <h1 class="text-4xl font-extrabold text-white tracking-tight italic">
                    İzleme <span class="text-indigo-500">Listem</span>
                </h1>

                {{-- İzleme listesinde toplam film sayısı --}}
                <div class="bg-slate-800/50 border border-slate-700 px-6 py-3 rounded-2xl shadow-lg backdrop-blur-sm flex items-center gap-4 mt-4 md:mt-0">
                    <div class="w-10 h-10 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400">
                        <i class="fas fa-list-ul"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[10px] font-bold uppercase tracking-wider">Bekleyen</p>
                        <p class="text-xl font-black text-white leading-none">{{ $totalMovies }} <span class="text-sm font-normal text-slate-500">film</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- BACKEND ARAMA ÇUBUĞU FORMU --}}
        <div class="mb-12">
            <form action="{{ route('movies.watchlist') }}" method="GET" class="relative group max-w-md mx-auto md:mx-0">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>

                <input type="text" name="search" value="{{ request('search') }}" placeholder="Listemde ara (Enter'a bas)..."
                    class="block w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-600 shadow-xl">

                @if(request('search'))
                    <a href="{{ route('movies.watchlist') }}"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </form>
        </div>

        @if ($movies->isEmpty())
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
                <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-popcorn text-3xl text-slate-600"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Listende hiç film yok.</h3>
                <p class="text-slate-500 mb-8 italic text-sm">İzlemek istediğin yeni filmleri keşfetme vakti!</p>
                <div class="flex justify-center gap-4 mt-6">
                    <a href="{{ route('movies.create') }}"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-xl font-bold transition-all inline-block shadow-lg shadow-indigo-600/20">
                        + Film Ara ve Ekle
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
                @foreach ($movies as $movie)
                    <div x-data="{ modalOpen: false }" class="relative">

                        {{-- KART TASARIMI --}}
                        <div class="group relative bg-slate-900 rounded-3xl overflow-hidden border border-slate-800 transition-all hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/10">

                            <div @click="modalOpen = true" class="aspect-[2/3] relative overflow-hidden bg-slate-800 cursor-pointer">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-all z-20 flex items-center justify-center">
                                    <i class="fas fa-search-plus opacity-0 group-hover:opacity-100 text-white text-5xl drop-shadow-lg scale-50 group-hover:scale-100 transition-all duration-300"></i>
                                </div>

                                @if ($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                        class="w-full h-full object-cover relative z-10" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950 relative z-10">
                                        <i class="fas fa-image text-4xl"></i>
                                    </div>
                                @endif

                                {{-- Puan bölümü YOK --}}

                                <div class="absolute top-4 right-4 z-30">
                                    <span class="bg-amber-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENECEK</span>
                                </div>
                            </div>

                            <div class="p-5">
                                <h4 class="text-white font-bold truncate mb-3" title="{{ $movie->title }}">
                                    {{ $movie->title }}
                                </h4>

                                <p class="text-indigo-400/80 text-[10px] font-bold uppercase tracking-wider mb-2 truncate"
                                    title="{{ $movie->director ?? 'Bilinmiyor' }}">
                                    <i class="fas fa-bullhorn mr-1"></i> {{ $movie->director ?? 'Bilinmiyor' }}
                                </p>

                                <div class="flex justify-between items-center mt-2 border-t border-slate-800/50 pt-3">
                                    <span class="text-slate-500 text-xs font-semibold">{{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}</span>
                                    @if ($movie->runtime)
                                        <span class="text-slate-400 text-[10px] font-mono bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">{{ $movie->runtime }} dk</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- MODAL İÇERİĞİ --}}
                        <template x-teleport="body">
                            <div x-show="modalOpen"
                                class="fixed inset-0 z-[99] flex items-center justify-center p-4 md:p-6 bg-slate-950/90 backdrop-blur-sm"
                                @keydown.escape.window="modalOpen = false" x-transition.opacity style="display: none;">

                                <div @click.away="modalOpen = false"
                                    class="bg-slate-900 border border-slate-800 w-full max-w-3xl max-h-[90vh] flex flex-col rounded-[2rem] md:rounded-[2.5rem] overflow-hidden shadow-2xl relative">

                                    <button @click="modalOpen = false"
                                        class="absolute top-4 right-4 z-50 bg-slate-900/80 backdrop-blur-sm hover:bg-slate-800 text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors border border-slate-700 shadow-xl">
                                        <i class="fas fa-times"></i>
                                    </button>

                                    <div class="overflow-y-auto custom-scrollbar flex-1 w-full">
                                        <div class="flex flex-col md:hidden">
                                            <div class="relative w-full h-64 shrink-0 bg-slate-950">
                                                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                                    class="w-full h-full object-cover object-top" loading="lazy">
                                                <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent"></div>
                                            </div>

                                            <div class="px-6 pb-6 -mt-8 relative z-10">
                                                <h2 class="text-3xl font-black text-white leading-tight mb-2 pr-8">{{ $movie->title }}</h2>

                                                <p class="text-indigo-400 text-sm font-bold tracking-widest uppercase mb-4 flex items-center gap-2">
                                                    <i class="fas fa-video"></i> {{ $movie->director ?? 'Yönetmen Bilgisi Yok' }}
                                                </p>

                                                <div class="flex items-center gap-3 flex-wrap mb-6">
                                                    <span class="bg-indigo-500/10 text-indigo-400 px-3 py-1.5 rounded-lg font-bold text-xs uppercase border border-indigo-500/20">
                                                        {{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}
                                                    </span>
                                                    @if ($movie->runtime)
                                                        <div class="flex items-center gap-1.5 text-slate-400 text-xs font-bold bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700">
                                                            <i class="fas fa-clock"></i> <span>{{ $movie->runtime }} dk</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <h3 class="text-slate-500 font-black mb-2 uppercase text-[10px] tracking-widest">Özet</h3>
                                                <p class="text-slate-300 leading-relaxed text-sm italic">{{ $movie->overview ?? 'Özet yok.' }}</p>
                                            </div>
                                        </div>

                                        <div class="hidden md:flex flex-row h-full">
                                            <div class="w-2/5 shrink-0 bg-slate-950 relative">
                                                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                                    class="w-full h-full object-cover" loading="lazy">
                                                <div class="absolute inset-0 bg-gradient-to-r from-transparent to-slate-900/50"></div>
                                            </div>

                                            <div class="w-3/5 p-10 flex flex-col justify-center">
                                                <h2 class="text-4xl font-black text-white mb-2 leading-tight pr-8">{{ $movie->title }}</h2>

                                                <p class="text-indigo-400 text-sm font-bold tracking-widest uppercase mb-6 flex items-center gap-2 drop-shadow-md">
                                                    <i class="fas fa-video"></i> {{ $movie->director ?? 'Yönetmen Bilgisi Yok' }}
                                                </p>

                                                <div class="flex items-center gap-4 mb-8 flex-wrap">
                                                    <span class="bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-lg font-bold text-xs uppercase border border-indigo-500/20">{{ $movie->release_date }}</span>
                                                    @if ($movie->runtime)
                                                        <div class="flex items-center gap-1.5 text-slate-400 text-xs font-bold bg-slate-800 px-3 py-1 rounded-lg">
                                                            <i class="fas fa-clock"></i> <span>{{ $movie->runtime }} dk</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <h3 class="text-slate-500 font-black mb-3 uppercase text-[10px] tracking-widest">Özet</h3>
                                                <p class="text-slate-300 leading-relaxed text-base italic">{{ $movie->overview ?? 'Özet yok.' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- SABİT (STICKY) ALT BAR --}}
                                    <div class="bg-slate-900 border-t border-slate-800 p-4 md:p-6 shrink-0 flex flex-wrap gap-4 justify-start relative z-20">

                                        {{-- 1. İzledim Butonu --}}
                                        <form action="{{ route('movies.update', $movie) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-2xl text-sm font-black transition-all shadow-lg shadow-indigo-600/20">
                                                İzledim Olarak İşaretle
                                            </button>
                                        </form>

                                        {{-- 2. YENİ EKLENEN: Akıllı Fragman Butonu --}}
                                        <a href="https://www.youtube.com/results?search_query={{ urlencode($movie->title . ' resmi fragman trailer') }}"
                                           target="_blank" rel="noopener noreferrer"
                                           class="bg-red-600 hover:bg-red-500 text-white px-6 py-3 rounded-2xl text-sm font-black transition-all shadow-lg shadow-red-600/20 flex items-center gap-2">
                                            <i class="fab fa-youtube text-lg"></i> Fragman
                                        </a>

                                        {{-- 3. Sil Butonu --}}
                                        <form action="{{ route('movies.destroy', $movie) }}" method="POST"
                                            onsubmit="return confirm('Bu filmi listenden silmek istediğinize emin misiniz?')">
                                            @csrf @method('DELETE')
                                            <button class="bg-slate-800 hover:bg-red-600/20 hover:text-red-500 text-slate-400 px-6 py-3 rounded-2xl text-sm font-black transition-all border border-slate-700 flex items-center">
                                                <i class="fas fa-trash-alt mr-2"></i> Sil
                                            </button>
                                        </form>

                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>

            {{-- SAYFALANDIRMA (PAGINATION) LİNKLERİ --}}
            <div class="mt-12 mb-8">
                {{ $movies->links() }}
            </div>
        @endif
    </div>
@endsection
