@extends('layouts.app')

@section('title', $user->name . ' Film Arşivi')

@section('content')
    <div class="container mx-auto">
        {{-- ÜST BİLGİ ALANI --}}
        <div class="mb-12 text-center">
            <div class="inline-block p-1 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-500 mb-6 shadow-2xl">
                <div class="bg-slate-900 rounded-full p-4">
                    <i class="fas fa-user text-4xl text-white"></i>
                </div>
            </div>
            <h1 class="text-4xl font-black text-white mb-2">{{ $user->name }}</h1>
            <p class="text-slate-400 font-medium">Film Arşivi & Koleksiyonu</p>
            
            <div class="flex items-center justify-center gap-6 mt-8">
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl px-6 py-4 shadow-xl">
                    <span class="block text-2xl font-black text-white">{{ $totalMovies }}</span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Toplam Film</span>
                </div>
                <div class="bg-slate-800/50 backdrop-blur-sm border border-slate-700/50 rounded-2xl px-6 py-4 shadow-xl border-emerald-500/20">
                    <span class="block text-2xl font-black text-emerald-400">{{ $watchedCount }}</span>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">İzlenen</span>
                </div>
            </div>
        </div>

        @if($movies->isEmpty())
            <div class="bg-slate-800/30 border-2 border-dashed border-slate-700 rounded-3xl p-16 text-center">
                <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-film text-3xl text-slate-600"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Henüz Film Yok</h3>
                <p class="text-slate-400">Bu kullanıcı henüz arşivine film eklememiş.</p>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                @foreach ($movies as $movie)
                    <div class="group relative bg-slate-800 rounded-2xl overflow-hidden shadow-xl transition-all duration-500 hover:-translate-y-2 hover:shadow-indigo-500/10 border border-white/5">
                        {{-- Poster --}}
                        <div class="aspect-[2/3] relative overflow-hidden">
                            @if ($movie->poster_path)
                                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                     alt="{{ $movie->title }}"
                                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            @else
                                <div class="w-full h-full bg-slate-700 flex flex-col items-center justify-center p-4">
                                    <i class="fas fa-image text-slate-500 text-3xl mb-2"></i>
                                    <span class="text-slate-400 text-[10px] text-center font-bold px-2 uppercase">Poster Yok</span>
                                </div>
                            @endif

                            {{-- Overlay Bilgileri --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/20 to-transparent opacity-0 group-hover:opacity-100 transition-all duration-500">
                                <div class="absolute bottom-4 left-4 right-4 translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-star text-yellow-500 text-xs"></i>
                                        <span class="text-white text-xs font-black">{{ number_format($movie->rating, 1) }}</span>
                                    </div>
                                    <p class="text-white font-bold text-xs line-clamp-2">{{ $movie->title }}</p>
                                </div>
                            </div>

                            {{-- Rating Badge (Always visible on mobile) --}}
                            @if ($movie->rating)
                                <div class="absolute top-3 left-3 z-10 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                    <div class="bg-black/60 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10">
                                        <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                        <span class="text-xs font-black">{{ number_format($movie->rating, 1) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 mb-12">
                {{ $movies->links() }}
            </div>
        @endif
    </div>
@endsection
