@extends('layouts.app')

@section('title', 'İzlenecek Filmlerim')

@section('content')
    <div class="container mx-auto" x-data="watchlistFilter()">

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
            <form @submit.prevent="submitSearch()" class="relative group max-w-md mx-auto md:mx-0">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>

                <input type="text" x-model="search" placeholder="Listemde ara (Enter'a bas)..."
                    class="block w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-600 shadow-xl">

                <button type="button" x-show="search.length > 0" @click="clearSearch()"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors">
                    <i class="fas fa-times-circle"></i>
                </button>
            </form>
        </div>

        {{-- SIRALAMA DROPDOWN'I --}}
        <div class="mb-8 flex justify-center md:justify-start">
            <div class="flex items-center gap-3">
                <span class="text-slate-500 text-xs font-bold uppercase tracking-widest hidden sm:inline-block">
                    <i class="fas fa-sort mr-1"></i> Sırala
                </span>
                <select x-model="sort" @change="_fetch()"
                    class="bg-slate-900 border border-slate-700 text-white text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                    <option value="updated_at">Son Eklenen</option>
                    <option value="title">İsme Göre (A-Z)</option>
                    <option value="rating">TMDB Puanı</option>
                    <option value="release_date">Yayın Tarihi</option>
                    <option value="runtime">Süre</option>
                </select>

                <span class="text-slate-500 text-xs font-bold uppercase tracking-widest hidden sm:inline-block ml-4">
                    <i class="fas fa-filter mr-1"></i> Tür
                </span>
                <select x-model="genre" @change="_fetch()"
                    class="bg-slate-900 border border-slate-700 text-white text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer max-w-[150px] truncate">
                    <option value="">Tüm Türler</option>
                    @foreach($availableGenres as $g)
                        <option value="{{ $g }}">
                            {{ $g }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Film Grid Alanı (AJAX ile güncellenecek) --}}
        <div x-ref="movieGrid" class="relative">
            {{-- Loading Spinner --}}
            <div x-show="loading" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center rounded-3xl" style="display: none;">
                <div class="flex flex-col items-center gap-3">
                    <i class="fas fa-spinner fa-spin text-indigo-500 text-3xl"></i>
                    <span class="text-slate-400 text-sm font-medium">Yükleniyor...</span>
                </div>
            </div>
            @include('movies.partials._watchlist_grid')
        </div>

        {{-- TOPLU İŞLEM ARAÇ ÇUBUĞU (STICKY BOTTOM BAR) --}}
        <div x-show="selectedMovies.length > 0"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-full opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-full opacity-0"
             class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-[95%] max-w-4xl"
             style="display: none;">

            <div class="bg-slate-900/95 backdrop-blur-xl border border-indigo-500/50 rounded-2xl p-4 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-4">
                {{-- Seçim Sayacı ve İptal --}}
                <div class="flex items-center gap-4 text-white">
                    <div class="flex items-center gap-2 bg-indigo-500/20 text-indigo-400 px-3 py-1.5 rounded-lg font-bold">
                        <i class="fas fa-check-double"></i>
                        <span x-text="selectedMovies.length"></span> film seçildi
                    </div>
                     <button @click="selectedMovies = []" class="text-slate-400 hover:text-white text-sm font-semibold underline underline-offset-4 transition-colors">
                        Seçimi Temizle
                    </button>
                </div>

                {{-- İşlem Butonları --}}
                <div class="flex flex-wrap items-center justify-center gap-3">

                    {{-- Koleksiyona Ekle --}}
                    @if($collections->isNotEmpty())
                        <div x-data="{ showDropdown: false }" class="relative">
                            <button @click="showDropdown = !showDropdown" @click.away="showDropdown = false"
                                class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold px-4 py-2 rounded-xl transition-colors flex items-center gap-2 border border-slate-700">
                                <i class="fas fa-folder-plus text-teal-400"></i> Koleksiyona Ekle <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>

                            <div x-show="showDropdown" style="display: none;"
                                class="absolute bottom-full mb-2 right-0 w-56 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden z-50">
                                <form action="{{ route('movies.bulk.collection') }}" method="POST" class="flex flex-col max-h-48 overflow-y-auto">
                                    @csrf
                                    <template x-for="id in selectedMovies">
                                        <input type="hidden" name="movie_ids[]" :value="id">
                                    </template>
                                    @foreach($collections as $collection)
                                        <button type="submit" name="collection_id" value="{{ $collection->id }}"
                                            class="text-left px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-white border-b border-slate-700/50 last:border-0 transition-colors">
                                            <i class="fas fa-{{ $collection->icon }} mr-2" style="color: {{ $collection->color }}"></i> {{ $collection->name }}
                                        </button>
                                    @endforeach
                                </form>
                            </div>
                        </div>
                    @endif

                    {{-- İzlendi İşaretle --}}
                    <form action="{{ route('movies.bulk.watched') }}" method="POST">
                        @csrf
                        <template x-for="id in selectedMovies">
                            <input type="hidden" name="movie_ids[]" :value="id">
                        </template>
                        <button type="submit" class="bg-emerald-500/20 hover:bg-emerald-500 hover:text-white text-emerald-400 text-sm font-bold px-4 py-2 rounded-xl transition-colors border border-emerald-500/30 flex items-center gap-2">
                            <i class="fas fa-check"></i> İzlendi İşaretle
                        </button>
                    </form>

                    {{-- Sil --}}
                    <form action="{{ route('movies.bulk.delete') }}" method="POST" onsubmit="return confirm('Seçili filmleri arşivden silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')">
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selectedMovies">
                            <input type="hidden" name="movie_ids[]" :value="id">
                        </template>
                        <button type="submit" class="bg-red-500/20 hover:bg-red-500 hover:text-white text-red-500 text-sm font-bold px-4 py-2 rounded-xl transition-colors border border-red-500/30 flex items-center gap-2">
                            <i class="fas fa-trash-alt"></i> Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function watchlistFilter() {
    return {
        selectedMovies: [],
        loading: false,
        search: '{{ request('search') }}',
        sort: '{{ $sort }}',
        genre: '{{ request('genre') }}',

        init() {
            this.$refs.movieGrid.addEventListener('click', (e) => {
                const link = e.target.closest('nav[role="navigation"] a');
                if (link) {
                    e.preventDefault();
                    this.fetchFromUrl(link.href);
                }
            });

            window.addEventListener('popstate', () => {
                const params = new URLSearchParams(window.location.search);
                this.search = params.get('search') || '';
                this.sort = params.get('sort') || 'updated_at';
                this.genre = params.get('genre') || '';
                this._fetch(false);
            });
        },

        submitSearch() {
            this.selectedMovies = [];
            this._fetch();
        },

        clearSearch() {
            this.search = '';
            this.selectedMovies = [];
            this._fetch();
        },

        _buildUrl() {
            const params = new URLSearchParams();
            if (this.search) params.set('search', this.search);
            if (this.sort && this.sort !== 'updated_at') params.set('sort', this.sort);
            if (this.genre) params.set('genre', this.genre);
            const qs = params.toString();
            return '{{ route("movies.watchlist") }}' + (qs ? '?' + qs : '');
        },

        async _fetch(pushState = true) {
            this.loading = true;
            const url = this._buildUrl();
            if (pushState) window.history.pushState({}, '', url);

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.$refs.movieGrid.innerHTML = await res.text();
                this.$nextTick(() => Alpine.initTree(this.$refs.movieGrid));
            } catch (err) {
                console.error('Filtre hatası:', err);
            }
            this.loading = false;
        },

        async fetchFromUrl(url) {
            this.loading = true;
            window.history.pushState({}, '', url);

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.$refs.movieGrid.innerHTML = await res.text();
                this.$nextTick(() => Alpine.initTree(this.$refs.movieGrid));
            } catch (err) {
                console.error('Sayfalama hatası:', err);
            }
            this.loading = false;
        }
    };
}
</script>
@endpush
