@extends('layouts.app')

@section('title', 'Yeni Film Keşfet')


@section('content')

    <div class="max-w-4xl mx-auto" x-data="movieSearch()">
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
                    class="w-full bg-slate-950 border border-slate-800 text-white pl-12 pr-24 py-5 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner placeholder-slate-700">

                {{-- 🆕 GELİŞMİŞ ARAMA BUTONU --}}
                <button @click="showAdvanced = !showAdvanced"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 px-3 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1"
                    :class="showAdvanced || hasAdvancedFilters()
                        ? 'bg-indigo-500 text-white'
                        : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white'">
                    <i class="fas fa-sliders-h"></i>
                    <span class="hidden sm:inline">Filtrele</span>
                    <span x-show="hasAdvancedFilters() && !showAdvanced"
                          class="bg-white text-indigo-600 text-[10px] font-black px-1.5 py-0.5 rounded-full ml-1"
                          x-text="countAdvancedFilters()" style="display: none;"></span>
                </button>

                <div x-show="loading" class="absolute right-20 top-5" style="display: none;">
                    <i class="fas fa-circle-notch fa-spin text-indigo-500 text-xl"></i>
                </div>
            </div>

            {{-- ============================================================================
                 📚 GELİŞMİŞ FİLTRELER PANELİ (TMDB Discover API)

                 Bu panel TMDB'nin Discover API'sini kullanarak gelişmiş filtreleme yapar.
                 Kullanıcı yıl, tür, puan gibi kriterlere göre film keşfedebilir.
            ============================================================================ --}}
            <div x-show="showAdvanced"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="mt-6 bg-slate-950/50 border border-slate-800 rounded-2xl p-5"
                 style="display: none;">

                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-white text-sm font-bold flex items-center gap-2">
                        <i class="fas fa-sliders-h text-indigo-400"></i>
                        Gelişmiş Filtreler
                    </h3>
                    <button @click="clearAdvancedFilters()"
                        type="button"
                        class="text-xs text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1">
                        <i class="fas fa-eraser"></i> Temizle
                    </button>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

                    {{-- 📅 YIL ARALIĞI --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                            <i class="fas fa-calendar text-indigo-400"></i> Yıl Aralığı
                        </label>
                        <div class="flex gap-2">
                            <input type="number" x-model="yearFrom" @change="fetchMovies()"
                                placeholder="1900" min="1900" max="2030"
                                class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-2 py-2 focus:ring-2 focus:ring-indigo-500 placeholder-slate-600">
                            <span class="text-slate-600 self-center text-xs">-</span>
                            <input type="number" x-model="yearTo" @change="fetchMovies()"
                                placeholder="2026" min="1900" max="2030"
                                class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-2 py-2 focus:ring-2 focus:ring-indigo-500 placeholder-slate-600">
                        </div>
                    </div>

                    {{-- 🎭 TÜR --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                            <i class="fas fa-theater-masks text-purple-400"></i> Tür
                        </label>
                        <select x-model="genre" @change="fetchMovies()"
                            class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-2 py-2 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                            <option value="">Tümü</option>
                            @foreach($tmdbGenres as $g)
                                <option value="{{ $g['id'] }}">{{ $g['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ⭐ MİNİMUM PUAN --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i> Min. Puan
                        </label>
                        <select x-model="minRating" @change="fetchMovies()"
                            class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-2 py-2 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                            <option value="">Hepsi</option>
                            <option value="9">9+ Başyapıt</option>
                            <option value="8">8+ Harika</option>
                            <option value="7">7+ İyi</option>
                            <option value="6">6+ Ortalama Üstü</option>
                            <option value="5">5+ Ortalama</option>
                        </select>
                    </div>

                    {{-- 🔄 SIRALAMA --}}
                    <div class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                            <i class="fas fa-sort text-emerald-400"></i> Sıralama
                        </label>
                        <select x-model="sortBy" @change="fetchMovies()"
                            class="w-full bg-slate-900 border border-slate-700 text-white text-sm rounded-lg px-2 py-2 focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                            <option value="popularity.desc">Popülerlik</option>
                            <option value="vote_average.desc">En Yüksek Puan</option>
                            <option value="primary_release_date.desc">En Yeni</option>
                            <option value="primary_release_date.asc">En Eski</option>
                            <option value="revenue.desc">En Çok Hasılat</option>
                        </select>
                    </div>

                </div>

                {{-- Sadece filtreyle keşfet butonu (query olmadan) --}}
                <div class="mt-4 flex items-center gap-3">
                    <button @click="discoverWithFilters()"
                        type="button"
                        :disabled="!hasAdvancedFilters()"
                        class="flex-1 py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2"
                        :class="hasAdvancedFilters()
                            ? 'bg-gradient-to-r from-indigo-500 to-purple-500 text-white hover:from-indigo-600 hover:to-purple-600'
                            : 'bg-slate-800 text-slate-600 cursor-not-allowed'">
                        <i class="fas fa-compass"></i>
                        Filtrelere Göre Keşfet
                    </button>
                    <p class="text-[10px] text-slate-600 hidden md:block">
                        veya yukarıda film adı yazarak ara
                    </p>
                </div>

                {{-- Aktif filtre özeti --}}
                <div x-show="hasAdvancedFilters()" class="mt-4 pt-4 border-t border-slate-800" style="display: none;">
                    <div class="flex flex-wrap gap-2">
                        <span class="text-[10px] text-slate-500">Aktif:</span>

                        <template x-if="yearFrom || yearTo">
                            <span class="inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-300 text-[10px] px-2 py-1 rounded-full">
                                <i class="fas fa-calendar"></i>
                                <span x-text="(yearFrom || '...') + '-' + (yearTo || '...')"></span>
                                <button @click="yearFrom = ''; yearTo = ''; fetchMovies()" class="hover:text-white ml-1">×</button>
                            </span>
                        </template>

                        <template x-if="genre">
                            <span class="inline-flex items-center gap-1 bg-purple-500/20 text-purple-300 text-[10px] px-2 py-1 rounded-full">
                                <i class="fas fa-theater-masks"></i>
                                <span x-text="getGenreName()"></span>
                                <button @click="genre = ''; fetchMovies()" class="hover:text-white ml-1">×</button>
                            </span>
                        </template>

                        <template x-if="minRating">
                            <span class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-300 text-[10px] px-2 py-1 rounded-full">
                                <i class="fas fa-star"></i>
                                <span x-text="minRating + '+'"></span>
                                <button @click="minRating = ''; fetchMovies()" class="hover:text-white ml-1">×</button>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ARAMA SONUÇLARI --}}
            <div x-show="results.length > 0" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                class="mt-6 bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl overflow-hidden divide-y divide-slate-700/50"
                style="display: none;">

                {{-- Sonuç başlığı --}}
                <div class="px-4 py-3 bg-slate-900/50 flex items-center justify-between">
                    <span class="text-xs text-slate-400">
                        <i class="fas fa-film mr-1"></i>
                        <span x-text="results.length"></span> sonuç
                        <span x-show="totalResults > 0" class="text-slate-600">
                            / <span x-text="totalResults"></span> toplam
                        </span>
                    </span>
                    <button @click="clearResults()" class="text-xs text-slate-500 hover:text-white transition-colors">
                        <i class="fas fa-times mr-1"></i> Kapat
                    </button>
                </div>

                <div class="max-h-[500px] overflow-y-auto" x-ref="resultsContainer">
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
                                <div class="min-w-0 flex-1">
                                    <div class="text-white font-bold truncate group-hover:text-indigo-400 transition-colors"
                                        x-text="movie.title"></div>
                                    <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                                        <span x-text="movie.release_date ? movie.release_date.substring(0,4) : '-'"></span>
                                        <span class="flex items-center gap-1" x-show="movie.vote_average">
                                            <i class="fas fa-star text-yellow-500"></i>
                                            <span x-text="movie.vote_average?.toFixed(1)"></span>
                                        </span>
                                    </div>
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

                    {{-- 🆕 DAHA FAZLA YÜKLE BUTONU --}}
                    <div x-show="hasMorePages()" class="p-4 border-t border-slate-700">
                        <button @click="loadMore()"
                            type="button"
                            :disabled="loadingMore"
                            class="w-full py-3 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 bg-slate-700 hover:bg-slate-600 text-white disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="loadingMore">
                                <span><i class="fas fa-circle-notch fa-spin mr-2"></i> Yükleniyor...</span>
                            </template>
                            <template x-if="!loadingMore">
                                <span><i class="fas fa-plus mr-2"></i> Daha Fazla Yükle (Sayfa <span x-text="currentPage + 1"></span>/<span x-text="totalPages"></span>)</span>
                            </template>
                        </button>
                    </div>
                </div>
            </div>

            <div x-show="query.length >= 2 && results.length === 0 && !loading && searched"
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

@push('scripts')
<script>
/**
 * 📚 FİLM ARAMA BİLEŞENİ (Sayfalama Destekli)
 *
 * Bu Alpine.js bileşeni hem basit arama hem de gelişmiş filtreleme destekler.
 * TMDB API'nin iki farklı endpoint'ini kullanır:
 *
 * 1. /search/movie → İsimle arama (query varken)
 * 2. /discover/movie → Filtrelerle keşif (query yokken)
 *
 * 📚 SAYFALAMA (Infinite Scroll / Load More)
 *
 * TMDB her istekte 20 sonuç döner. "Daha fazla yükle" butonu ile
 * sonraki sayfalar yüklenir ve mevcut listeye eklenir.
 *
 * State:
 * - currentPage: Şu anki sayfa numarası
 * - totalPages: Toplam sayfa sayısı
 * - loadingMore: Daha fazla yüklenirken true
 */
function movieSearch() {
    // TMDB tür listesi (Blade'den JavaScript'e aktarıyoruz)
    const genres = @json($tmdbGenres);

    return {
        // Arama state
        query: '',
        results: [],
        loading: false,
        searched: false,

        // 🆕 Sayfalama state
        currentPage: 1,
        totalPages: 1,
        totalResults: 0,
        loadingMore: false,

        // Gelişmiş filtre state
        showAdvanced: false,
        yearFrom: '',
        yearTo: '',
        genre: '',
        minRating: '',
        sortBy: 'popularity.desc',

        /**
         * 📚 URL PARAMETRELERİNİ OLUŞTUR
         *
         * Bu yardımcı metod, tüm filtreleri URL parametrelerine dönüştürür.
         * fetchMovies, discoverWithFilters ve loadMore tarafından kullanılır.
         * DRY prensibi - kendini tekrar etme!
         */
        _buildParams(page = 1) {
            const params = new URLSearchParams();

            if (this.query.length >= 2) {
                params.set('query', this.query);
            }

            // Gelişmiş filtreler varsa discover modunu aktifle
            if (this.hasAdvancedFilters() || page > 1) {
                params.set('discover', '1');
                if (this.yearFrom) params.set('year_from', this.yearFrom);
                if (this.yearTo) params.set('year_to', this.yearTo);
                if (this.genre) params.set('genre', this.genre);
                if (this.minRating) params.set('min_rating', this.minRating);
                if (this.sortBy) params.set('sort_by', this.sortBy);
                params.set('page', page);
            }

            return params;
        },

        /**
         * Film arama - query varsa search, yoksa ve filtre varsa discover
         * İlk arama her zaman 1. sayfadan başlar
         */
        fetchMovies() {
            // Query çok kısa ve filtre de yoksa temizle
            if (this.query.length < 2 && !this.hasAdvancedFilters()) {
                this.clearResults();
                return;
            }

            this.loading = true;
            this.searched = true;
            this.currentPage = 1; // 🆕 Yeni aramada sayfayı sıfırla

            const params = this._buildParams(1);

            fetch(`/movies/api-search?${params.toString()}`)
                .then(res => {
                    if (!res.ok) throw new Error('Sunucu hatası');
                    return res.json();
                })
                .then(data => {
                    // 🆕 Discover modu sayfalama bilgisi döner
                    if (data.results !== undefined) {
                        this.results = data.results;
                        this.currentPage = data.page || 1;
                        this.totalPages = data.total_pages || 1;
                        this.totalResults = data.total_results || 0;
                    } else {
                        // Normal arama (sayfalama yok)
                        this.results = data;
                        this.currentPage = 1;
                        this.totalPages = 1;
                        this.totalResults = data.length;
                    }
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Hata:', err);
                    this.loading = false;
                });
        },

        /**
         * 🆕 DAHA FAZLA YÜKLE
         *
         * Sonraki sayfayı yükler ve mevcut listeye ekler.
         * Spread operator (...) ile iki diziyi birleştirir.
         */
        loadMore() {
            if (this.loadingMore || !this.hasMorePages()) return;

            this.loadingMore = true;
            const nextPage = this.currentPage + 1;
            const params = this._buildParams(nextPage);

            fetch(`/movies/api-search?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    if (data.results !== undefined) {
                        // Mevcut sonuçlara ekle (duplicate kontrolü ile)
                        const existingIds = new Set(this.results.map(m => m.id));
                        const newResults = data.results.filter(m => !existingIds.has(m.id));
                        this.results = [...this.results, ...newResults];
                        this.currentPage = data.page;
                        this.totalPages = data.total_pages || 1;
                    }
                    this.loadingMore = false;
                })
                .catch(err => {
                    console.error('Hata:', err);
                    this.loadingMore = false;
                });
        },

        /**
         * 🆕 Daha fazla sayfa var mı?
         */
        hasMorePages() {
            return this.currentPage < this.totalPages;
        },

        /**
         * 🆕 Sonuçları ve sayfalamayı temizle
         */
        clearResults() {
            this.results = [];
            this.searched = false;
            this.currentPage = 1;
            this.totalPages = 1;
            this.totalResults = 0;
        },

        /**
         * Sadece filtrelerle keşfet (query olmadan)
         */
        discoverWithFilters() {
            if (!this.hasAdvancedFilters()) return;

            this.loading = true;
            this.searched = true;
            this.currentPage = 1;

            const params = this._buildParams(1);

            fetch(`/movies/api-search?${params.toString()}`)
                .then(res => res.json())
                .then(data => {
                    if (data.results !== undefined) {
                        this.results = data.results;
                        this.currentPage = data.page || 1;
                        this.totalPages = data.total_pages || 1;
                        this.totalResults = data.total_results || 0;
                    } else {
                        this.results = data;
                    }
                    this.loading = false;
                })
                .catch(err => {
                    console.error('Hata:', err);
                    this.loading = false;
                });
        },

        /**
         * Gelişmiş filtrelerin aktif olup olmadığını kontrol et
         */
        hasAdvancedFilters() {
            return this.yearFrom || this.yearTo || this.genre || this.minRating;
        },

        /**
         * Aktif filtre sayısını hesapla
         */
        countAdvancedFilters() {
            let count = 0;
            if (this.yearFrom || this.yearTo) count++;
            if (this.genre) count++;
            if (this.minRating) count++;
            return count;
        },

        /**
         * Tüm gelişmiş filtreleri temizle
         */
        clearAdvancedFilters() {
            this.yearFrom = '';
            this.yearTo = '';
            this.genre = '';
            this.minRating = '';
            this.sortBy = 'popularity.desc';
            if (this.query.length >= 2) {
                this.fetchMovies();
            } else {
                this.clearResults();
            }
        },

        /**
         * Genre ID'den tür adını bul
         */
        getGenreName() {
            const found = genres.find(g => g.id == this.genre);
            return found ? found.name : '';
        }
    };
}
</script>
@endpush
@endsection
