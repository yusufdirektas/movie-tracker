@extends('layouts.app')

@section('title', $collection->name . ' - ' . $user->name)

@section('content')
    <div class="container mx-auto">
        {{-- ÜST BİLGİ ALANI --}}
        <div class="mb-12 text-center">
            <div class="w-24 h-24 bg-slate-800 rounded-3xl mx-auto mb-6 flex items-center justify-center shadow-2xl border border-white/10 relative overflow-hidden group">
                <div class="absolute inset-0 opacity-20 group-hover:scale-150 transition-transform duration-700" style="background-color: {{ $collection->color }}"></div>
                <i class="fas fa-{{ $collection->icon }} text-4xl relative z-10" style="color: {{ $collection->color }}"></i>
            </div>
            
            <h1 class="text-4xl font-black text-white mb-2">{{ $collection->name }}</h1>
            <p class="text-slate-400 font-medium">
                <span class="text-slate-500">Hazırlayan:</span> {{ $user->name }}
            </p>

            <div class="flex items-center justify-center gap-3 mt-6">
                <span class="bg-indigo-500/10 text-indigo-400 px-4 py-1.5 rounded-full text-xs font-bold border border-indigo-500/20">
                    <i class="fas fa-folder mr-1.5"></i> KOLEKSİYON
                </span>
                <span class="bg-slate-800/50 text-slate-400 px-4 py-1.5 rounded-full text-xs font-bold border border-slate-700/50">
                    <i class="fas fa-film mr-1.5"></i> {{ $movies->total() }} FİLM
                </span>
            </div>
        </div>

        @if($movies->isEmpty())
            <div class="bg-slate-800/30 border-2 border-dashed border-slate-700 rounded-3xl p-16 text-center">
                <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-layer-group text-slate-600"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Koleksiyon Henüz Boş</h3>
                <p class="text-slate-400">Bu koleksiyona henüz herhangi bir film eklenmemiş.</p>
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
