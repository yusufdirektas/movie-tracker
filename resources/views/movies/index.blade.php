@extends('layouts.app')

@section('title', 'Film İstatistiklerim')

@section('content')
    {{-- Alpine.js State Wrapper: selectedMovies dizisi seçilen filmlerin ID'lerini tutar --}}
    <div class="container mx-auto" x-data="movieFilter()">

        <div class="mb-12">
            <div class="flex flex-col md:flex-row justify-between items-end mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl md:text-5xl font-black text-white tracking-tighter uppercase italic">
                        Film <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Arşivim</span>
                    </h1>

                    {{-- PAYLAŞ BUTONU VE MENÜSÜ --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open"
                            class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-black px-4 py-2 rounded-xl transition-all border border-slate-700 flex items-center gap-2">
                            <i class="fas fa-share-alt {{ Auth::user()->is_public ? 'text-emerald-400' : '' }}"></i>
                            {{ Auth::user()->is_public ? 'Paylaşılıyor' : 'Paylaş' }}
                        </button>

                        <div x-show="open" @click.away="open = false" style="display: none;"
                            class="absolute top-full left-0 mt-2 w-72 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl z-50 p-4">

                            <div class="mb-4 pb-4 border-b border-slate-800">
                                <form action="{{ route('privacy.archive.toggle') }}" method="POST" class="flex items-center justify-between">
                                    @csrf
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Arşiv Paylaşımı</h4>
                                        <p class="text-[10px] text-slate-500">Arşiviniz herkese açık olsun mu?</p>
                                    </div>
                                    <button type="submit"
                                        class="w-12 h-6 rounded-full relative transition-colors duration-200 focus:outline-none {{ Auth::user()->is_public ? 'bg-emerald-500' : 'bg-slate-700' }}">
                                        <div class="absolute top-1/2 -translate-y-1/2 left-1 w-4 h-4 rounded-full bg-white transition-transform duration-200 {{ Auth::user()->is_public ? 'translate-x-6' : '' }}"></div>
                                    </button>
                                </form>
                            </div>

                            @if(Auth::user()->is_public)
                                <div class="mb-4">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Paylaşım Linki</label>
                                    <div style="display:flex; gap:8px; width:100%; box-sizing:border-box;">
                                        <input type="text" readonly value="{{ Auth::user()->share_token ? route('public.archive', ['token' => Auth::user()->share_token]) : '' }}" id="shareUrl"
                                            style="flex:1 1 0%; min-width:0; background:#1e293b; border:none; border-radius:8px; font-size:12px; color:#cbd5e1; padding:8px 12px; box-sizing:border-box; overflow:hidden; text-overflow:ellipsis;">
                                        <button onclick="copyToClipboard('shareUrl')"
                                            style="flex-shrink:0; background:#4f46e5; color:white; padding:8px; border-radius:8px; border:none; cursor:pointer;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                <form action="{{ route('privacy.regenerate-token') }}" method="POST" onsubmit="return confirm('Link yenilendiğinde eski link artık çalışmayacaktır. Emin misiniz?')">
                                    @csrf
                                    <button type="submit" class="text-[10px] text-slate-500 hover:text-red-400 transition-colors underline">
                                        Link Yenile
                                    </button>
                                </form>
                            @else
                                <div class="text-center py-2">
                                    <i class="fas fa-lock text-slate-600 mb-2 block"></i>
                                    <p class="text-[10px] text-slate-500 italic">Paylaşımı aktif ederek listeni herkese açık hale getirebilirsin.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <a href="{{ route('movies.import') }}"
                    class="group flex items-center gap-2 text-slate-500 hover:text-indigo-400 transition-colors mt-4 md:mt-0">
                    <span
                        class="text-xs font-bold uppercase tracking-widest border-b border-slate-700 group-hover:border-indigo-400 pb-0.5">Toplu
                        Liste Yükle</span>
                    <i class="fas fa-file-import"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div
                    class="bg-slate-800/50 border border-slate-700 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex items-center gap-4 hover:border-indigo-500/30 transition-colors">
                    <div class="w-12 h-12 bg-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400">
                        <i class="fas fa-video text-xl"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Toplam Film</p>
                        <p class="text-2xl font-black text-white">{{ $totalMovies }}</p>
                    </div>
                </div>

                <div
                    class="bg-slate-800/50 border border-slate-700 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex items-center gap-4 hover:border-emerald-500/30 transition-colors">
                    <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center text-emerald-400">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">İzlenen</p>
                        <p class="text-2xl font-black text-white">{{ $watchedCount }}</p>
                    </div>
                </div>

                <div
                    class="bg-slate-800/50 border border-slate-700 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex items-center gap-4 hover:border-amber-500/30 transition-colors">
                    <div class="w-12 h-12 bg-amber-500/20 rounded-2xl flex items-center justify-center text-amber-400">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                    <div>
                        <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Ekran Süresi</p>
                        <p class="text-lg font-black text-white leading-tight">
                            {{ $totalHours }} <span class="text-xs font-normal text-slate-500">saat</span>
                            {{ $remainingMinutes }} <span class="text-xs font-normal text-slate-500">dk</span>
                        </p>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-br from-indigo-900/50 to-slate-900 border border-indigo-500/30 p-1 rounded-3xl shadow-lg relative overflow-hidden group">
                    @if ($highestRated)
                        <div class="absolute inset-0 bg-cover bg-center opacity-20 group-hover:opacity-40 transition-opacity duration-500"
                            style="background-image: url('https://image.tmdb.org/t/p/w500{{ $highestRated->poster_path }}')">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                        <div class="relative p-5 h-full flex flex-col justify-center z-10">
                            <p
                                class="text-indigo-300 text-[10px] font-bold uppercase tracking-widest mb-1 shadow-black drop-shadow-md">
                                Zirvedeki Film</p>
                            <p class="text-white font-black truncate text-lg shadow-black drop-shadow-lg">
                                {{ $highestRated->title }}</p>
                            <div
                                class="flex items-center gap-1 text-yellow-400 text-sm font-bold mt-1 shadow-black drop-shadow-md">
                                <i class="fas fa-star"></i> {{ number_format($highestRated->rating ?? 0, 1) }}
                            </div>
                        </div>
                    @else
                        <div class="p-5 flex items-center justify-center h-full text-slate-500 text-xs italic">
                            Henüz veri yok
                        </div>
                    @endif
                </div>

            </div>
        </div>

        {{-- ARAMA ÇUBUĞU (AJAX) --}}
        <div class="mb-8">
            <form @submit.prevent="submitSearch()" class="relative group max-w-md mx-auto md:mx-0">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>

                <input type="text" x-model="search" placeholder="Arşivimde ara (Enter'a bas)..."
                    class="block w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-600 shadow-xl">

                <button x-show="search" @click.prevent="clearSearch()" type="button"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors" style="display: none;">
                    <i class="fas fa-times-circle"></i>
                </button>
            </form>
        </div>

        {{-- Filtreleme Butonları (AJAX) --}}
        <div class="mb-8 flex justify-center md:justify-start">
            <div class="inline-flex bg-slate-900/80 p-1.5 rounded-2xl border border-slate-800 shadow-inner">

               {{-- Tümü --}}
    <button @click="setFilter('all')"
       role="tab"
       :aria-selected="filter === 'all' ? 'true' : 'false'"
       class="filter-btn"
       :class="filter === 'all' ? 'active-all active' : ''">
      <span class="btn-icon"><i class="fas fa-layer-group"></i></span>
      <span class="btn-label">Tümü</span>
    </button>

    <div class="filter-divider" aria-hidden="true"></div>

    {{-- Favorilerim --}}
    <button @click="setFilter('favorites')"
       role="tab"
       :aria-selected="filter === 'favorites' ? 'true' : 'false'"
       class="filter-btn"
       :class="filter === 'favorites' ? 'active-favorites active' : ''">
      <span class="btn-icon"><i class="fas fa-heart"></i></span>
      <span class="btn-label">Favorilerim</span>
    </button>

            </div>
        </div>

        {{-- SIRALAMA & TÜR DROPDOWN'LARI (AJAX) --}}
        <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="text-slate-500 text-xs font-bold uppercase tracking-widest hidden sm:inline-block">
                    <i class="fas fa-sort mr-1"></i> Sırala
                </span>
                <select x-model="sort" @change="_fetch()"
                    class="bg-slate-900 border border-slate-700 text-white text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                    <option value="updated_at">Son Eklenen</option>
                    <option value="title">İsme Göre (A-Z)</option>
                    <option value="rating">TMDB Puanı</option>
                    <option value="personal_rating">Kişisel Puan</option>
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
                        <option value="{{ $g }}">{{ $g }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 📚 DIŞA AKTAR (EXPORT) BUTONLARI --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('movies.export.csv') }}" class="bg-indigo-600/20 text-indigo-400 hover:bg-indigo-500 hover:text-white px-4 py-2 flex items-center gap-2 rounded-xl text-sm font-bold transition-all border border-indigo-500/30">
                    <i class="fas fa-file-csv"></i> CSV İndir
                </a>
                <a href="{{ route('movies.export.json') }}" class="bg-purple-600/20 text-purple-400 hover:bg-purple-500 hover:text-white px-4 py-2 flex items-center gap-2 rounded-xl text-sm font-bold transition-all border border-purple-500/30">
                    <i class="fas fa-file-code"></i> JSON İndir
                </a>
            </div>
        </div>
        {{-- FİLM GRİD ALANI (AJAX ile güncellenir) --}}
        <div x-ref="movieGrid" class="relative transition-opacity duration-300" :class="{ 'opacity-40 pointer-events-none': loading }">
            {{-- Yükleniyor göstergesi --}}
            <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-50 pointer-events-none" style="display: none;">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-indigo-500 border-t-transparent"></div>
            </div>
            @include('movies.partials._grid')
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
@push('scripts')
<script>
function movieFilter() {
    return {
        selectedMovies: [],
        loading: false,
        filter: '{{ request('filter', 'all') }}',
        search: '{{ request('search') }}',
        sort: '{{ $sort }}',
        genre: '{{ request('genre') }}',

        init() {
            // Sayfalandırma linkleri için event delegation
            this.$refs.movieGrid.addEventListener('click', (e) => {
                const link = e.target.closest('nav[role="navigation"] a');
                if (link) {
                    e.preventDefault();
                    this.fetchFromUrl(link.href);
                }
            });

            // Tarayıcı geri/ileri butonları
            window.addEventListener('popstate', () => {
                const params = new URLSearchParams(window.location.search);
                this.filter = params.get('filter') || 'all';
                this.search = params.get('search') || '';
                this.sort = params.get('sort') || 'updated_at';
                this.genre = params.get('genre') || '';
                this._fetch(false);
            });
        },

        setFilter(newFilter) {
            this.filter = newFilter;
            this.selectedMovies = [];
            this._fetch();
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
            if (this.filter && this.filter !== 'all') params.set('filter', this.filter);
            if (this.search) params.set('search', this.search);
            if (this.sort && this.sort !== 'updated_at') params.set('sort', this.sort);
            if (this.genre) params.set('genre', this.genre);
            const qs = params.toString();
            return '{{ route("movies.index") }}' + (qs ? '?' + qs : '');
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
                this.$refs.movieGrid.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } catch (err) {
                console.error('Sayfa hatası:', err);
            }
            this.loading = false;
        }
    };
}

function copyToClipboard(elementId) {
    var copyText = document.getElementById(elementId);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);

    const btn = event.currentTarget;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i>';
    btn.classList.replace('bg-indigo-600', 'bg-emerald-600');

    setTimeout(() => {
        btn.innerHTML = oldHtml;
        btn.classList.replace('bg-emerald-600', 'bg-indigo-600');
    }, 2000);
}
</script>
@endpush
@endsection
