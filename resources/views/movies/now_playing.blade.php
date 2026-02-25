@extends('layouts.app')

@section('title', 'Vizyondaki Filmler')

@section('content')
<div class="container mx-auto py-8">
    <div class="mb-12 flex items-center justify-between">
        <h1 class="text-4xl font-extrabold text-white tracking-tight italic flex items-center gap-3">
            Vizyondaki <span class="text-emerald-500">Filmler</span> 🎟️
        </h1>
        <a href="{{ route('movies.index') }}" class="text-slate-500 hover:text-emerald-400 font-bold uppercase tracking-widest text-xs transition-colors border-b border-transparent hover:border-emerald-400 pb-1">
            <i class="fas fa-arrow-left mr-1"></i> Arşive Dön
        </a>
    </div>

    @if(count($nowPlaying) > 0)
        <div class="mb-10 bg-slate-900/80 border border-emerald-500/30 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex flex-col sm:flex-row items-center gap-4">
            <div class="w-14 h-14 bg-emerald-500/20 rounded-2xl flex shrink-0 items-center justify-center text-emerald-400">
                <i class="fas fa-ticket-alt text-2xl"></i>
            </div>
            <div>
                <p class="text-slate-300 text-sm leading-relaxed">
                    Şu anda sinemalarda gösterimde olan ve henüz arşivine eklemediğin en güncel filmler burada. Hafta sonu planı yapmak için harika bir fırsat!
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
            @foreach($nowPlaying as $movie)

                <div x-data="{ modalOpen: false }" class="h-full">

                    <div @click="modalOpen = true" class="group relative bg-slate-900 border border-slate-800/50 rounded-3xl overflow-hidden transition-all hover:scale-105 hover:shadow-2xl hover:shadow-emerald-500/10 hover:border-emerald-500/30 flex flex-col h-full cursor-pointer">

                        <div class="aspect-[2/3] relative overflow-hidden bg-slate-800 shrink-0">
                            @if(isset($movie['poster_path']) && $movie['poster_path'])
                                <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}" alt="{{ $movie['title'] }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950">
                                    <i class="fas fa-image text-4xl"></i>
                                </div>
                            @endif

                            <div class="absolute top-3 left-3 z-10">
                                <div class="bg-black/80 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10 shadow-lg">
                                    <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                    <span class="text-xs font-black">{{ number_format($movie['vote_average'] ?? 0, 1) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 flex flex-col flex-grow justify-center text-center">
                            <h4 class="text-white font-bold text-sm line-clamp-2" title="{{ $movie['title'] }}">
                                {{ $movie['title'] }}
                            </h4>
                            <span class="text-slate-500 text-xs font-semibold mt-2">
                                {{ isset($movie['release_date']) && strlen($movie['release_date']) > 4 ? substr($movie['release_date'], 0, 4) : '-' }}
                            </span>
                        </div>
                    </div>

                    <template x-teleport="body">
                        <div x-show="modalOpen" class="fixed inset-0 z-[99] flex items-center justify-center px-4 bg-slate-950/90 backdrop-blur-sm" @keydown.escape.window="modalOpen = false" x-transition.opacity style="display: none;">

                            <div @click.away="modalOpen = false" class="bg-slate-900 border border-slate-800 w-full max-w-3xl rounded-[2.5rem] overflow-hidden shadow-2xl relative">

                                <button @click="modalOpen = false" class="absolute top-4 right-4 z-20 bg-slate-800/80 hover:bg-slate-700 text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors">
                                    <i class="fas fa-times"></i>
                                </button>

                                <div class="flex flex-col md:flex-row">
                                    <div class="w-full md:w-2/5 aspect-[2/3] md:aspect-auto">
                                        @if(isset($movie['poster_path']) && $movie['poster_path'])
                                            <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950 min-h-[300px]">
                                                <i class="fas fa-image text-4xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="w-full md:w-3/5 p-6 md:p-10 flex flex-col justify-center">
                                        <h2 class="text-2xl md:text-3xl font-black text-white mb-4 leading-tight">{{ $movie['title'] }}</h2>

                                        <div class="flex items-center gap-4 mb-6 flex-wrap">
                                            <span class="bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-lg font-bold text-xs uppercase border border-emerald-500/20">
                                                {{ isset($movie['release_date']) && strlen($movie['release_date']) > 4 ? substr($movie['release_date'], 0, 4) : 'Bilinmiyor' }}
                                            </span>
                                            <div class="flex items-center gap-1.5 text-yellow-400">
                                                <i class="fas fa-star text-base"></i>
                                                <span class="text-lg font-black">{{ number_format($movie['vote_average'] ?? 0, 1) }}</span>
                                            </div>
                                        </div>

                                        <h3 class="text-slate-500 font-black mb-2 uppercase text-[10px] tracking-widest">Özet</h3>
                                        <p class="text-slate-300 leading-relaxed text-sm italic max-h-32 overflow-y-auto custom-scrollbar pr-2 mb-6">
                                            {{ !empty($movie['overview']) ? $movie['overview'] : 'Bu film için henüz Türkçe özet bulunmuyor.' }}
                                        </p>

                                        <div class="mt-auto pt-6 border-t border-slate-800 flex flex-col sm:flex-row gap-3">

                                            <div class="flex-1" x-data="{ isLoading: false, isAdded: false, errorMessage: '' }">
    <button
        type="button"
        @click="
            isLoading = true;
            errorMessage = '';
            fetch('{{ route('movies.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tmdb_id: {{ $movie['id'] }},
                    is_watched: 0
                })
            })
            .then(response => response.json())
            .then(data => {
                isLoading = false;
                if(data.success) {
                    isAdded = true;
                } else {
                    errorMessage = data.message;
                }
            })
            .catch(error => {
                isLoading = false;
                errorMessage = 'Bir hata oluştu.';
            });
        "
        :disabled="isLoading || isAdded"
        :class="isAdded ? 'bg-slate-700 text-emerald-400 hover:bg-slate-600 shadow-slate-700/20' : 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/20'"
        class="w-full px-4 py-3 rounded-2xl text-sm font-black transition-all shadow-lg flex items-center justify-center gap-2 disabled:opacity-100 disabled:cursor-not-allowed">

        <i x-show="isLoading" class="fas fa-spinner fa-spin" style="display: none;"></i>

        <i x-show="isAdded && !isLoading" class="fas fa-check" style="display: none;"></i>

        <i x-show="!isAdded && !isLoading" class="fas fa-plus"></i>

        <span x-text="isLoading ? 'Ekleniyor...' : (isAdded ? 'Eklendi' : 'Listeme Ekle')"></span>
    </button>

    <p x-show="errorMessage" x-text="errorMessage" style="display: none;" class="text-[10px] text-red-400 font-bold mt-2 text-center uppercase tracking-wider"></p>
</div>

                                            <a href="https://www.youtube.com/results?search_query={{ urlencode($movie['title'] . ' resmi fragman') }}" target="_blank" class="flex-1 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white border border-red-500/20 hover:border-red-600 px-4 py-3 rounded-2xl text-sm font-black transition-all flex items-center justify-center gap-2">
                                                <i class="fab fa-youtube text-lg"></i> Fragman İzle
                                            </a>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

            @endforeach
        </div>
    @else
        <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
            <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                <i class="fas fa-popcorn text-3xl text-slate-600"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Gösterilecek Film Yok</h3>
            <p class="text-slate-500 mb-8 italic text-sm">Şu an vizyonda olan tüm popüler filmleri zaten arşivine eklemişsin. Harikasın!</p>
        </div>
    @endif
</div>
@endsection
