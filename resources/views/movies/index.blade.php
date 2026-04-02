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

                    {{-- PAYLAŞ BUTONU VE MENÜSÜ (AJAX) --}}
                    {{--
                    @KAVRAM: Alpine.js ile AJAX Toggle

                    Pattern:
                    1. x-data → Component state (isPublic, loading, shareUrl)
                    2. @click → toggle() fonksiyonunu çağır
                    3. fetch() → Backend'e POST request
                    4. JSON response → State'i güncelle
                    5. x-show, :class → UI otomatik değişir

                    Avantajlar:
                    - Sayfa yenilenmez
                    - Anlık geri bildirim
                    - Smooth animasyonlar
                    --}}
                    <div x-data="{
                        open: false,
                        isPublic: {{ Auth::user()->is_public ? 'true' : 'false' }},
                        shareUrl: '{{ Auth::user()->share_token ? route('public.archive', ['token' => Auth::user()->share_token]) : '' }}',
                        loading: false,

                        async togglePrivacy() {
                            if (this.loading) return;
                            this.loading = true;

                            try {
                                const response = await fetch('{{ route('privacy.archive.toggle') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }
                                });

                                const data = await response.json();

                                if (data.success) {
                                    this.isPublic = data.is_public;
                                    this.shareUrl = data.share_url || '';
                                    // Toast göster (varsa)
                                    if (typeof showToast === 'function') {
                                        showToast(data.message, 'success');
                                    }
                                }
                            } catch (error) {
                                console.error('Toggle error:', error);
                            } finally {
                                this.loading = false;
                            }
                        },

                        async regenerateToken() {
                            if (!confirm('Link yenilendiğinde eski link artık çalışmayacaktır. Emin misiniz?')) return;
                            if (this.loading) return;
                            this.loading = true;

                            try {
                                const response = await fetch('{{ route('privacy.regenerate-token') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    }
                                });

                                const data = await response.json();

                                if (data.success) {
                                    this.shareUrl = data.share_url;
                                    // Input'u güncelle
                                    const input = document.getElementById('shareUrl');
                                    if (input) input.value = data.share_url;
                                    if (typeof showToast === 'function') {
                                        showToast(data.message, 'success');
                                    }
                                }
                            } catch (error) {
                                console.error('Regenerate error:', error);
                            } finally {
                                this.loading = false;
                            }
                        }
                    }" class="relative">
                        <button @click="open = !open"
                            class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-black px-4 py-2 rounded-xl transition-all border border-slate-700 flex items-center gap-2">
                            <i class="fas fa-share-alt" :class="isPublic ? 'text-emerald-400' : ''"></i>
                            <span x-text="isPublic ? 'Paylaşılıyor' : 'Paylaş'"></span>
                        </button>

                        <div x-show="open" @click.away="open = false" x-cloak
                            class="absolute top-full left-0 mt-2 w-72 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl z-50 p-4">

                            <div class="mb-4 pb-4 border-b border-slate-800">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-bold text-white">Arşiv Paylaşımı</h4>
                                        <p class="text-[10px] text-slate-500">Arşiviniz herkese açık olsun mu?</p>
                                    </div>
                                    <button @click="togglePrivacy()"
                                        :disabled="loading"
                                        class="w-12 h-6 rounded-full relative transition-colors duration-200 focus:outline-none disabled:opacity-50"
                                        :class="isPublic ? 'bg-emerald-500' : 'bg-slate-700'">
                                        <div class="absolute top-1/2 -translate-y-1/2 left-1 w-4 h-4 rounded-full bg-white transition-transform duration-200"
                                             :class="isPublic ? 'translate-x-6' : ''"></div>
                                    </button>
                                </div>
                            </div>

                            {{-- Public olduğunda göster --}}
                            <template x-if="isPublic">
                                <div>
                                    <div class="mb-4">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Paylaşım Linki</label>
                                        <div style="display:flex; gap:8px; width:100%; box-sizing:border-box;">
                                            <input type="text" readonly :value="shareUrl" id="shareUrl"
                                                style="flex:1 1 0%; min-width:0; background:#1e293b; border:none; border-radius:8px; font-size:12px; color:#cbd5e1; padding:8px 12px; box-sizing:border-box; overflow:hidden; text-overflow:ellipsis;">
                                            <button @click="copyToClipboard('shareUrl')"
                                                style="flex-shrink:0; background:#4f46e5; color:white; padding:8px; border-radius:8px; border:none; cursor:pointer;">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <button @click="regenerateToken()"
                                        :disabled="loading"
                                        class="text-[10px] text-slate-500 hover:text-red-400 transition-colors underline disabled:opacity-50">
                                        <span x-show="!loading">Link Yenile</span>
                                        <span x-show="loading"><i class="fas fa-spinner fa-spin"></i></span>
                                    </button>
                                </div>
                            </template>

                            {{-- Private olduğunda göster --}}
                            <template x-if="!isPublic">
                                <div class="text-center py-2">
                                    <i class="fas fa-lock text-slate-600 mb-2 block"></i>
                                    <p class="text-[10px] text-slate-500 italic">Paylaşımı aktif ederek listeni herkese açık hale getirebilirsin.</p>
                                </div>
                            </template>
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

        {{-- SIRALAMA & GELİŞMİŞ DROPDOWN'LARI (AJAX) --}}
        <div class="mb-4 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3 flex-wrap">
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

                {{-- 🆕 GELİŞMİŞ FİLTRELER BUTONU --}}
                <button @click="showAdvanced = !showAdvanced"
                    class="ml-2 px-4 py-2 text-sm font-bold rounded-xl transition-all flex items-center gap-2"
                    :class="showAdvanced || hasAdvancedFilters()
                        ? 'bg-indigo-500 text-white'
                        : 'bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white border border-slate-700'">
                    <i class="fas fa-sliders-h"></i>
                    <span class="hidden sm:inline">Gelişmiş</span>
                    {{-- Aktif filtre sayısı badge --}}
                    <span x-show="hasAdvancedFilters() && !showAdvanced"
                          class="bg-white text-indigo-600 text-xs font-black px-1.5 py-0.5 rounded-full"
                          x-text="countAdvancedFilters()" style="display: none;"></span>
                </button>
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

        {{-- ============================================================================
             📚 GELİŞMİŞ FİLTRELER PANELİ (Advanced Filters Panel)

             Bu panel x-show ile gösterilir/gizlenir.
             x-transition direktifleri smooth animasyon sağlar.

             @KAVRAM: x-transition
             - enter: Panel görünürken uygulanan class'lar
             - leave: Panel gizlenirken uygulanan class'lar
             - transform: Translate, scale gibi dönüşümler
             - opacity: Şeffaflık geçişleri

             @KAVRAM: x-model
             - İki yönlü veri bağlama (two-way data binding)
             - Input değişince Alpine state güncellenir
             - Alpine state değişince input güncellenir
        ============================================================================ --}}
        <div x-show="showAdvanced"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="mb-8 bg-slate-900/80 border border-slate-700 rounded-2xl p-6 backdrop-blur-sm"
             style="display: none;">

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-white font-bold flex items-center gap-2">
                    <i class="fas fa-sliders-h text-indigo-400"></i>
                    Gelişmiş Filtreler
                </h3>
                <button @click="clearAdvancedFilters()"
                    class="text-xs text-slate-500 hover:text-red-400 transition-colors flex items-center gap-1">
                    <i class="fas fa-eraser"></i> Temizle
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

                {{-- 🎭 TÜRLER (Genre) --}}
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-masks-theater text-pink-400"></i> Tür
                    </label>
                    <select x-model="genre" @change="_fetch()"
                        class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        <option value="">Tüm Türler</option>
                        @foreach($availableGenres as $g)
                            <option value="{{ $g }}">{{ $g }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- 📅 YIL ARALIĞI --}}
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-calendar text-indigo-400"></i> Yayın Yılı
                    </label>
                    <div class="flex gap-2">
                        <input type="number" x-model="yearFrom" @change="_fetch()"
                            placeholder="1900" min="1900" max="2030"
                            class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-600">
                        <span class="text-slate-600 self-center">-</span>
                        <input type="number" x-model="yearTo" @change="_fetch()"
                            placeholder="2026" min="1900" max="2030"
                            class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-600">
                    </div>
                </div>

                {{-- ⏱️ SÜRE ARALIĞI --}}
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-clock text-amber-400"></i> Süre (dk)
                    </label>
                    <div class="flex gap-2">
                        <input type="number" x-model="runtimeMin" @change="_fetch()"
                            placeholder="0" min="0" max="500"
                            class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-600">
                        <span class="text-slate-600 self-center">-</span>
                        <input type="number" x-model="runtimeMax" @change="_fetch()"
                            placeholder="∞" min="0" max="500"
                            class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-600">
                    </div>
                </div>

                {{-- ⭐ MİNİMUM PUAN --}}
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-star text-yellow-400"></i> Min. TMDB Puanı
                    </label>
                    <select x-model="ratingMin" @change="_fetch()"
                        class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        <option value="">Hepsi</option>
                        <option value="9">9+ Başyapıt</option>
                        <option value="8">8+ Harika</option>
                        <option value="7">7+ İyi</option>
                        <option value="6">6+ Ortalama Üstü</option>
                        <option value="5">5+ Ortalama</option>
                    </select>
                </div>

                {{-- 🎬 MEDYA TİPİ (Film/Dizi) --}}
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-film text-purple-400"></i> Medya Tipi
                    </label>
                    <select x-model="mediaType" @change="_fetch()"
                        class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                        <option value="">Film & Dizi</option>
                        <option value="movie">Sadece Filmler</option>
                        <option value="tv">Sadece Diziler</option>
                    </select>
                </div>

                {{-- 🎥 YÖNETMEN --}}
                <div class="space-y-2 lg:col-span-5">
                    <label class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                        <i class="fas fa-user-tie text-emerald-400"></i> Yönetmen
                    </label>
                    <input type="text" x-model="director" @input.debounce.500ms="_fetch()"
                        list="directorSuggestions"
                        placeholder="Yönetmen adı yazın..."
                        class="w-full bg-slate-800 border border-slate-700 text-white text-sm rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-600">
                    {{-- 📚 DATALIST: Native HTML5 autocomplete önerisi --}}
                    <datalist id="directorSuggestions">
                        @foreach($availableDirectors as $d)
                            <option value="{{ $d }}">
                        @endforeach
                    </datalist>
                </div>

            </div>

            {{-- Aktif filtre özeti --}}
            <div x-show="hasAdvancedFilters()" class="mt-4 pt-4 border-t border-slate-800" style="display: none;">
                <div class="flex flex-wrap gap-2">
                    <span class="text-xs text-slate-500">Aktif filtreler:</span>

                    <template x-if="yearFrom || yearTo">
                        <span class="inline-flex items-center gap-1 bg-indigo-500/20 text-indigo-300 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-calendar"></i>
                            <span x-text="(yearFrom || '...') + ' - ' + (yearTo || '...')"></span>
                            <button @click="yearFrom = ''; yearTo = ''; _fetch()" class="hover:text-white ml-1">×</button>
                        </span>
                    </template>

                    <template x-if="runtimeMin || runtimeMax">
                        <span class="inline-flex items-center gap-1 bg-amber-500/20 text-amber-300 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-clock"></i>
                            <span x-text="(runtimeMin || '0') + ' - ' + (runtimeMax || '∞') + ' dk'"></span>
                            <button @click="runtimeMin = ''; runtimeMax = ''; _fetch()" class="hover:text-white ml-1">×</button>
                        </span>
                    </template>

                    <template x-if="ratingMin">
                        <span class="inline-flex items-center gap-1 bg-yellow-500/20 text-yellow-300 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-star"></i>
                            <span x-text="ratingMin + '+'"></span>
                            <button @click="ratingMin = ''; _fetch()" class="hover:text-white ml-1">×</button>
                        </span>
                    </template>

                    <template x-if="mediaType">
                        <span class="inline-flex items-center gap-1 bg-purple-500/20 text-purple-300 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-film"></i>
                            <span x-text="mediaType === 'movie' ? 'Film' : 'Dizi'"></span>
                            <button @click="mediaType = ''; _fetch()" class="hover:text-white ml-1">×</button>
                        </span>
                    </template>

                    <template x-if="director">
                        <span class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 text-xs px-2 py-1 rounded-full">
                            <i class="fas fa-user-tie"></i>
                            <span x-text="director"></span>
                            <button @click="director = ''; _fetch()" class="hover:text-white ml-1">×</button>
                        </span>
                    </template>
                </div>
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

                {{-- İşlem Butonları (AJAX) --}}
                <div class="flex flex-wrap items-center justify-center gap-3">

                    {{-- Koleksiyona Ekle --}}
                    @if($collections->isNotEmpty())
                        <div x-data="{ showDropdown: false }" class="relative">
                            <button @click="showDropdown = !showDropdown" @click.away="showDropdown = false"
                                class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-bold px-4 py-2 rounded-xl transition-colors flex items-center gap-2 border border-slate-700">
                                <i class="fas fa-folder-plus text-teal-400"></i> Koleksiyona Ekle <i class="fas fa-chevron-down text-xs ml-1"></i>
                            </button>

                            <div x-show="showDropdown" x-cloak
                                class="absolute bottom-full mb-2 right-0 w-56 bg-slate-800 border border-slate-700 rounded-xl shadow-xl overflow-hidden z-50">
                                <div class="flex flex-col max-h-48 overflow-y-auto">
                                    @foreach($collections as $collection)
                                        <button type="button"
                                            @click="bulkAddToCollection({{ $collection->id }}); showDropdown = false"
                                            class="text-left px-4 py-3 text-sm text-slate-300 hover:bg-slate-700 hover:text-white border-b border-slate-700/50 last:border-0 transition-colors">
                                            <i class="fas fa-{{ $collection->icon }} mr-2" style="color: {{ $collection->color }}"></i> {{ $collection->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- İzlendi İşaretle --}}
                    <button type="button" @click="bulkMarkWatched()"
                        class="bg-emerald-500/20 hover:bg-emerald-500 hover:text-white text-emerald-400 text-sm font-bold px-4 py-2 rounded-xl transition-colors border border-emerald-500/30 flex items-center gap-2">
                        <i class="fas fa-check"></i> İzlendi İşaretle
                    </button>

                    {{-- Sil --}}
                    <button type="button" @click="bulkDelete()"
                        class="bg-red-500/20 hover:bg-red-500 hover:text-white text-red-500 text-sm font-bold px-4 py-2 rounded-xl transition-colors border border-red-500/30 flex items-center gap-2">
                        <i class="fas fa-trash-alt"></i> Sil
                    </button>
                </div>
            </div>
        </div>
    </div>
@push('scripts')
<script>
/**
 * 📚 ALPINE.JS BİLEŞEN FONKSİYONU
 *
 * Alpine.js'de x-data="movieFilter()" şeklinde bir fonksiyon çağrıldığında,
 * bu fonksiyonun döndürdüğü obje, o elementin "state"i olur.
 *
 * @KAVRAM: Reaktivite
 * - State değiştiğinde (örn: this.search = 'matrix')
 * - Alpine otomatik olarak DOM'u günceller (x-model, x-show, x-text vs.)
 *
 * @KAVRAM: Methods
 * - Obje içindeki fonksiyonlar, o komponentin metodlarıdır
 * - @click="setFilter('all')" → setFilter metodu çağrılır
 */
function movieFilter() {
    return {
        // ==========================================
        // 📦 STATE (Durum Değişkenleri)
        // ==========================================
        selectedMovies: [],
        loading: false,

        // Temel filtreler
        filter: '{{ request('filter', 'all') }}',
        search: '{{ request('search') }}',
        sort: '{{ $sort }}',
        genre: '{{ request('genre') }}',

        // 🆕 Gelişmiş filtreler
        showAdvanced: {{ !empty(array_filter($advancedFilters)) ? 'true' : 'false' }},
        yearFrom: '{{ $advancedFilters['yearFrom'] ?? '' }}',
        yearTo: '{{ $advancedFilters['yearTo'] ?? '' }}',
        runtimeMin: '{{ $advancedFilters['runtimeMin'] ?? '' }}',
        runtimeMax: '{{ $advancedFilters['runtimeMax'] ?? '' }}',
        ratingMin: '{{ $advancedFilters['ratingMin'] ?? '' }}',
        director: '{{ $advancedFilters['director'] ?? '' }}',
        mediaType: '{{ $advancedFilters['mediaType'] ?? '' }}',

        // ==========================================
        // 🚀 LIFECYCLE HOOKS (Yaşam Döngüsü)
        // ==========================================

        /**
         * init() metodu, Alpine bileşeni DOM'a bağlandığında otomatik çalışır.
         * jQuery'deki $(document).ready() veya Vue'daki mounted() gibi.
         */
        init() {
            // Sayfalandırma linkleri için event delegation
            this.$refs.movieGrid.addEventListener('click', (e) => {
                const link = e.target.closest('nav[role="navigation"] a');
                if (link) {
                    e.preventDefault();
                    this.fetchFromUrl(link.href);
                }
            });

            // Tarayıcı geri/ileri butonları (History API)
            window.addEventListener('popstate', () => {
                const params = new URLSearchParams(window.location.search);
                this.filter = params.get('filter') || 'all';
                this.search = params.get('search') || '';
                this.sort = params.get('sort') || 'updated_at';
                this.genre = params.get('genre') || '';
                // 🆕 Gelişmiş filtreleri de geri yükle
                this.yearFrom = params.get('year_from') || '';
                this.yearTo = params.get('year_to') || '';
                this.runtimeMin = params.get('runtime_min') || '';
                this.runtimeMax = params.get('runtime_max') || '';
                this.ratingMin = params.get('rating_min') || '';
                this.director = params.get('director') || '';
                this.mediaType = params.get('media_type') || '';
                this._fetch(false);
            });
        },

        // ==========================================
        // 🎯 ACTION METHODS (Eylem Metodları)
        // ==========================================

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

        /**
         * 🆕 Gelişmiş filtrelerin aktif olup olmadığını kontrol eder.
         * UI'da badge göstermek için kullanılır.
         */
        hasAdvancedFilters() {
            return this.yearFrom || this.yearTo || this.runtimeMin || this.runtimeMax ||
                   this.ratingMin || this.director || this.mediaType;
        },

        /**
         * 🆕 Aktif gelişmiş filtre sayısını hesaplar (badge için).
         */
        countAdvancedFilters() {
            let count = 0;
            if (this.yearFrom || this.yearTo) count++;
            if (this.runtimeMin || this.runtimeMax) count++;
            if (this.ratingMin) count++;
            if (this.director) count++;
            if (this.mediaType) count++;
            return count;
        },

        /**
         * 🆕 Tüm gelişmiş filtreleri temizler.
         */
        clearAdvancedFilters() {
            this.yearFrom = '';
            this.yearTo = '';
            this.runtimeMin = '';
            this.runtimeMax = '';
            this.ratingMin = '';
            this.director = '';
            this.mediaType = '';
            this._fetch();
        },

        // ==========================================
        // 🔧 HELPER METHODS (Yardımcı Metodlar)
        // ==========================================

        /**
         * URL query string'i oluşturur.
         *
         * 📚 URLSearchParams API'si:
         * - Modern JavaScript'te URL parametreleri oluşturmak için kullanılır
         * - Otomatik encoding yapar (boşluk → %20, türkçe karakterler vs.)
         */
        _buildUrl() {
            const params = new URLSearchParams();

            // Temel filtreler
            if (this.filter && this.filter !== 'all') params.set('filter', this.filter);
            if (this.search) params.set('search', this.search);
            if (this.sort && this.sort !== 'updated_at') params.set('sort', this.sort);
            if (this.genre) params.set('genre', this.genre);

            // 🆕 Gelişmiş filtreler
            if (this.yearFrom) params.set('year_from', this.yearFrom);
            if (this.yearTo) params.set('year_to', this.yearTo);
            if (this.runtimeMin) params.set('runtime_min', this.runtimeMin);
            if (this.runtimeMax) params.set('runtime_max', this.runtimeMax);
            if (this.ratingMin) params.set('rating_min', this.ratingMin);
            if (this.director) params.set('director', this.director);
            if (this.mediaType) params.set('media_type', this.mediaType);

            const qs = params.toString();
            return '{{ route("movies.index") }}' + (qs ? '?' + qs : '');
        },

        /**
         * AJAX ile film listesini günceller.
         *
         * 📚 async/await:
         * - Promise tabanlı asenkron işlemleri senkron gibi yazmamızı sağlar
         * - fetch() bir Promise döner, await ile sonucunu bekleriz
         *
         * 📚 X-Requested-With header:
         * - Laravel'de request()->ajax() metodunun true dönmesi için gerekli
         * - AJAX isteklerini normal isteklerden ayırt etmeye yarar
         */
        async _fetch(pushState = true) {
            this.loading = true;
            const url = this._buildUrl();

            // 📚 History API: URL'i değiştir ama sayfa yenilenmesin
            if (pushState) window.history.pushState({}, '', url);

            try {
                const res = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.$refs.movieGrid.innerHTML = await res.text();

                // 📚 $nextTick: DOM güncellemesi bittikten sonra çalıştır
                // Alpine.initTree: Yeni eklenen HTML'deki Alpine direktiflerini aktifleştir
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
        },

        // ==========================================
        // 🎬 BULK ACTIONS (Toplu İşlemler - AJAX)
        // ==========================================

        /**
         * Toplu: İzlendi olarak işaretle
         */
        async bulkMarkWatched() {
            if (this.selectedMovies.length === 0) return;

            try {
                const response = await fetch('{{ route('movies.bulk.watched') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ movie_ids: this.selectedMovies })
                });

                const data = await response.json();

                if (data.success) {
                    // Grid'i yenile ve seçimi temizle
                    this.selectedMovies = [];
                    this._fetch(false);
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                }
            } catch (error) {
                console.error('Bulk watched error:', error);
            }
        },

        /**
         * Toplu: Koleksiyona ekle
         */
        async bulkAddToCollection(collectionId) {
            if (this.selectedMovies.length === 0) return;

            try {
                const response = await fetch('{{ route('movies.bulk.collection') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        movie_ids: this.selectedMovies,
                        collection_id: collectionId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.selectedMovies = [];
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                }
            } catch (error) {
                console.error('Bulk collection error:', error);
            }
        },

        /**
         * Toplu: Sil
         */
        async bulkDelete() {
            if (this.selectedMovies.length === 0) return;
            if (!confirm('Seçili filmleri arşivden silmek istediğinize emin misiniz? Bu işlem geri alınamaz!')) return;

            try {
                const response = await fetch('{{ route('movies.bulk.delete') }}', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ movie_ids: this.selectedMovies })
                });

                const data = await response.json();

                if (data.success) {
                    this.selectedMovies = [];
                    this._fetch(false);
                    if (typeof showToast === 'function') showToast(data.message, 'success');
                }
            } catch (error) {
                console.error('Bulk delete error:', error);
            }
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
