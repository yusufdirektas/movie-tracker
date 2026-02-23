@extends('layouts.app')

@section('title', 'Yeni Film Keşfet')


@section('content')

    <div class="max-w-2xl mx-auto" x-data="{
        query: '',
        results: [],
        loading: false,
        fetchMovies() {
            if (this.query.length < 2) { this.results = []; return; }
            this.loading = true;

            // Laravel rotası üzerinden güvenli arama yapıyoruz
            fetch(`/movies/api-search?query=${encodeURIComponent(this.query)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Sunucu hatası');
                    return res.json();
                })
                .then(data => {
                    this.results = data;
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Hata:', err);
                    this.loading = false;
                });
        }
    }">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show"
                class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-400 px-6 py-4 rounded-2xl mb-6 flex justify-between items-center shadow-lg backdrop-blur-sm">
                <div class="font-bold">
                    <i class="fas fa-check-circle mr-2 text-xl"></i> {{ session('success') }}
                </div>
                <button @click="show = false" class="text-emerald-400 hover:text-white transition-colors"><i
                        class="fas fa-times text-lg"></i></button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show"
                class="bg-red-500/10 border border-red-500/50 text-red-400 px-6 py-4 rounded-2xl mb-6 flex justify-between items-center shadow-lg backdrop-blur-sm">
                <div class="font-bold">
                    <i class="fas fa-exclamation-triangle mr-2 text-xl"></i> {{ session('error') }}
                </div>
                <button @click="show = false" class="text-red-400 hover:text-white transition-colors"><i
                        class="fas fa-times text-lg"></i></button>
            </div>
        @endif
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-black text-white italic">Yeni Film <span class="text-indigo-500">Keşfet</span></h1>
            <a href="{{ route('movies.index') }}"
                class="text-slate-500 hover:text-white transition-colors text-sm font-bold">
                <i class="fas fa-arrow-left mr-2"></i> Listeme Dön
            </a>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-8 rounded-[2.5rem] shadow-2xl relative">
            <label class="block text-slate-500 text-[10px] font-black uppercase tracking-[0.2em] mb-4">Film Adını Yazmaya
                Başla</label>

            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-600"></i>
                </div>
                <input type="text" x-model="query" @input.debounce.500ms="fetchMovies()"
                    placeholder="Örn: Örümcek Adam, Batman, Inception..."
                    class="w-full bg-slate-950 border border-slate-800 text-white pl-12 pr-12 py-5 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner placeholder-slate-700">

                <div x-show="loading" class="absolute right-5 top-5" style="display: none;">
                    <i class="fas fa-circle-notch fa-spin text-indigo-500 text-xl"></i>
                </div>
            </div>

            <div x-show="results.length > 0" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                class="absolute left-0 right-0 mt-3 mx-8 bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl z-50 overflow-hidden divide-y divide-slate-700/50"
                style="display: none;" @click.away="results = []">

                <template x-for="movie in results" :key="movie.id">
                    <form action="{{ route('movies.store') }}" method="POST"
                        class="flex items-center gap-4 p-4 hover:bg-indigo-600/10 transition-colors border-b border-slate-700/50 last:border-0 group">
                        @csrf
                        <input type="hidden" name="tmdb_id" :value="movie.id">

                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="w-12 h-18 flex-shrink-0">
                                <template x-if="movie.poster_path">
                                    <img :src="'https://image.tmdb.org/t/p/w92' + movie.poster_path"
                                        class="w-full h-full object-cover rounded-lg shadow-md">
                                </template>
                                <template x-if="!movie.poster_path">
                                    <div
                                        class="w-full h-full bg-slate-900 rounded-lg flex items-center justify-center border border-slate-700">
                                        <i class="fas fa-image text-slate-700"></i>
                                    </div>
                                </template>
                            </div>
                            <div class="min-w-0">
                                <div class="text-white font-bold truncate group-hover:text-indigo-400 transition-colors"
                                    x-text="movie.title"></div>
                                <div class="text-slate-500 text-xs"
                                    x-text="movie.release_date ? movie.release_date.substring(0,4) : '-'"></div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-2 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">

                            <button type="submit" name="is_watched" value="0"
                                class="bg-slate-700 hover:bg-amber-600 text-white p-3 rounded-xl transition-all tooltip-trigger"
                                title="İzlenecek Listesine Ekle">
                                <i class="fas fa-bookmark text-amber-400 hover:text-white"></i>
                            </button>

                            <button type="submit" name="is_watched" value="1"
                                class="bg-slate-700 hover:bg-emerald-600 text-white p-3 rounded-xl transition-all"
                                title="İzledim Olarak Ekle">
                                <i class="fas fa-check-circle text-emerald-400 hover:text-white"></i>
                            </button>

                        </div>
                    </form>
                </template>
            </div>

            <div x-show="query.length >= 2 && results.length === 0 && !loading"
                class="mt-6 text-center py-4 bg-slate-950/50 rounded-xl border border-slate-800" style="display: none;">
                <p class="text-slate-500 text-xs italic">"<span x-text="query" class="text-slate-300"></span>" için bir film
                    bulunamadı.</p>
            </div>
        </div>

        <p class="mt-8 text-center text-slate-600 text-[11px] leading-relaxed">
            Arama sonuçlarından birine tıkladığında film, afişi ve tüm detaylarıyla <br> otomatik olarak arşivine
            eklenecektir.
        </p>
    </div>
@endsection
