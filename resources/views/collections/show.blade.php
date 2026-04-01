@extends('layouts.app')

@section('title', $collection->name)

@section('content')
    <div class="container mx-auto">

        {{-- BAŞLIK --}}
        <div class="flex flex-col md:flex-row justify-between items-end mb-12">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-lg"
                    style="background-color: {{ $collection->color }}20; color: {{ $collection->color }};">
                    <i class="fas fa-{{ $collection->icon }}"></i>
                </div>
                <div>
                    <div class="flex items-center gap-6">
                    <h1 class="text-2xl md:text-4xl font-black text-white tracking-tight flex items-center gap-4">
                        <i class="fas fa-{{ $collection->icon }}" style="color: {{ $collection->color }}"></i>
                        {{ $collection->name }}
                    </h1>

                    {{-- PAYLAŞ BUTONU (AJAX) --}}
                    <div x-data="{
                        open: false,
                        isPublic: {{ $collection->is_public ? 'true' : 'false' }},
                        shareUrl: '{{ $collection->share_token ? route('public.collection', ['token' => $collection->share_token]) : '' }}',
                        loading: false,

                        async togglePrivacy() {
                            if (this.loading) return;
                            this.loading = true;

                            try {
                                const response = await fetch('{{ route('privacy.collection.toggle', $collection) }}', {
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
                                const formData = new FormData();
                                formData.append('collection_id', '{{ $collection->id }}');

                                const response = await fetch('{{ route('privacy.regenerate-token') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: formData
                                });

                                const data = await response.json();

                                if (data.success) {
                                    this.shareUrl = data.share_url;
                                    const input = document.getElementById('collUrl');
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
                                        <h4 class="text-sm font-bold text-white">Koleksiyon Paylaşımı</h4>
                                        <p class="text-[10px] text-slate-500">Bu koleksiyon herkese açık olsun mu?</p>
                                    </div>
                                    <button @click="togglePrivacy()"
                                        :disabled="loading"
                                        class="w-12 h-6 rounded-full relative transition-colors duration-200 focus:outline-none disabled:opacity-50"
                                        :class="isPublic ? 'bg-emerald-500' : 'bg-slate-700'">
                                        <div class="absolute top-1 left-1 w-4 h-4 rounded-full bg-white transition-transform duration-200"
                                             :class="isPublic ? 'translate-x-6' : ''"></div>
                                    </button>
                                </div>
                            </div>

                            <template x-if="isPublic">
                                <div>
                                    <div class="mb-4">
                                        <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest block mb-2">Paylaşım Linki</label>
                                        <div class="flex gap-2">
                                            <input type="text" readonly :value="shareUrl" id="collUrl"
                                                class="flex-1 bg-slate-800 border-none rounded-lg text-xs text-slate-300 py-2 px-3 focus:ring-1 focus:ring-indigo-500">
                                            <button @click="copyToClipboard('collUrl')"
                                                class="bg-indigo-600 hover:bg-indigo-500 text-white p-2 rounded-lg transition-colors">
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

                            <template x-if="!isPublic">
                                <div class="text-center py-2">
                                    <i class="fas fa-lock text-slate-600 mb-2 block"></i>
                                    <p class="text-[10px] text-slate-500 italic">Paylaşımı aktif ederek bu koleksiyonu başkalarına gönderebilirsin.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                    @if($collection->description)
                        <p class="text-slate-500 text-sm mt-1">{{ $collection->description }}</p>
                    @endif
                    <p class="text-slate-600 text-xs mt-1">{{ $collection->movies->count() }} film</p>
                </div>
            </div>

            <div class="flex items-center gap-3 mt-4 md:mt-0">
                <button onclick="openAddMovieModal()"
                    class="bg-teal-600 hover:bg-teal-500 text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition-all text-sm">
                    <i class="fas fa-plus"></i> Film Ekle
                </button>
                <a href="{{ route('collections.index') }}"
                    class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white px-5 py-2.5 rounded-xl font-bold flex items-center gap-2 transition-all text-sm border border-slate-700">
                    <i class="fas fa-arrow-left"></i> Geri
                </a>
            </div>
        </div>

        {{-- KOLEKSİYON KONTROLLERİ --}}
        <form method="GET" action="{{ route('collections.show', $collection) }}"
            class="mb-6 p-4 bg-slate-900/70 border border-slate-800 rounded-2xl">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label for="search" class="block text-xs text-slate-500 mb-1">Ara</label>
                    <input id="search" name="search" type="text" value="{{ $search }}"
                        placeholder="Koleksiyon içinde film ara..."
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-600">
                </div>
                <div>
                    <label for="watch" class="block text-xs text-slate-500 mb-1">Durum</label>
                    <select id="watch" name="watch"
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="all" {{ $watch === 'all' ? 'selected' : '' }}>Tümü</option>
                        <option value="watched" {{ $watch === 'watched' ? 'selected' : '' }}>İzlenen</option>
                        <option value="watchlist" {{ $watch === 'watchlist' ? 'selected' : '' }}>Watchlist</option>
                    </select>
                </div>
                <div>
                    <label for="sort" class="block text-xs text-slate-500 mb-1">Sıralama</label>
                    <select id="sort" name="sort"
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        <option value="manual" {{ $sort === 'manual' ? 'selected' : '' }}>Manuel (Sürükle)</option>
                        <option value="title_asc" {{ $sort === 'title_asc' ? 'selected' : '' }}>İsim (A-Z)</option>
                        <option value="title_desc" {{ $sort === 'title_desc' ? 'selected' : '' }}>İsim (Z-A)</option>
                        <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>En Yeni Eklenen</option>
                        <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>En Eski Eklenen</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between mt-3">
                <p class="text-xs text-slate-500">
                    {{ $collectionMovies->count() }} sonuç gösteriliyor
                    @if(!$canReorder)
                        <span class="text-amber-400 ml-1">• Sürükle-bırak kapalı (filtre/sıralama aktif)</span>
                    @endif
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('collections.show', $collection) }}"
                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition-colors">
                        Sıfırla
                    </a>
                    <button type="submit"
                        class="px-4 py-2 rounded-lg text-xs font-semibold text-white bg-teal-600 hover:bg-teal-500 transition-colors">
                        Uygula
                    </button>
                </div>
            </div>
        </form>

        {{-- FİLMLER --}}
        @if($collectionMovies->isEmpty())
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center"
                 id="dropZone"
                 ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-film text-4xl text-slate-600"></i>
                </div>
                <h3 class="text-white text-xl font-bold mb-2">{{ $collection->movies->isEmpty() ? 'Bu koleksiyon henüz boş' : 'Bu filtreye uygun film bulunamadı' }}</h3>
                <p class="text-slate-500 mb-6">
                    {{ $collection->movies->isEmpty()
                        ? '"Film Ekle" butonuna tıklayarak veya Film Arşivim sayfasından filmleri sürükleyip bırakarak ekleyebilirsin.'
                        : 'Filtreleri temizleyerek tüm koleksiyon filmlerini görebilirsin.' }}
                </p>
                <button onclick="openAddMovieModal()"
                    class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-3 rounded-xl font-bold transition-all">
                    <i class="fas fa-plus mr-2"></i> Film Ekle
                </button>
            </div>
        @else
            <form action="{{ route('collections.removeMovies', $collection) }}" method="POST" class="mb-4 flex justify-end">
                @csrf
                @method('DELETE')
                <div class="inline-flex items-center gap-2 bg-slate-900/70 border border-slate-700 rounded-xl px-3 py-2">
                    <input id="bulk-remove-input" type="text" name="movie_ids_json" class="hidden">
                    <button type="button"
                        onclick="toggleSelectAllCollectionMovies()"
                        class="text-xs font-semibold text-slate-400 hover:text-white transition-colors">
                        Tümünü Seç
                    </button>
                    <button type="submit"
                        onclick="return prepareBulkRemoveSubmission()"
                        class="text-xs font-semibold text-red-300 hover:text-red-200 transition-colors">
                        Seçilenleri Çıkar
                    </button>
                </div>
            </form>

            <div id="dropZone" class="w-full"
                 ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                <div id="collectionGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    @foreach($collectionMovies as $movie)
                        <div class="group relative collection-movie-item"
                            data-collection-movie-id="{{ $movie->id }}"
                            draggable="{{ $canReorder ? 'true' : 'false' }}"
                            @if($canReorder)
                                ondragstart="handleCollectionDragStart(event)"
                                ondragend="handleCollectionDragEnd(event)"
                                ondragover="handleCollectionDragOver(event)"
                                ondrop="handleCollectionDrop(event)"
                            @endif>
                            <label class="absolute top-3 left-3 z-30">
                                <span class="sr-only">{{ $movie->title }} seç</span>
                                <input type="checkbox" class="collection-bulk-checkbox w-4 h-4 rounded border-slate-500 bg-slate-900 text-red-500 focus:ring-red-500/40" value="{{ $movie->id }}">
                            </label>
                            <a href="{{ route('movies.show', $movie) }}"
                                class="block bg-slate-900 rounded-2xl overflow-hidden border border-slate-800/50 hover:border-teal-500/50 transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
                            <div class="aspect-[2/3] relative overflow-hidden bg-slate-800">
                                @if($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w300{{ $movie->poster_path }}"
                                        alt="{{ $movie->title }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-700">
                                        <i class="fas fa-image text-2xl"></i>
                                    </div>
                                @endif

                                @if($movie->rating)
                                    <div class="absolute top-2 left-2 z-30">
                                        <div class="bg-black/80 backdrop-blur-md text-white px-1.5 py-0.5 rounded-md flex items-center gap-1 border border-white/10 text-[10px] font-bold">
                                            <i class="fas fa-star text-yellow-500"></i>
                                            {{ number_format($movie->rating, 1) }}
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="p-2 sm:p-3">
                                <p class="text-white font-bold text-xs sm:text-sm leading-tight line-clamp-2 group-hover:text-teal-400 transition-colors" title="{{ $movie->title }}">
                                    {{ $movie->title }}
                                </p>
                                <p class="text-slate-500 text-[10px] sm:text-xs mt-1">
                                    {{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}
                                </p>
                            </div>
                        </a>

                        {{-- Koleksiyondan Çıkar --}}
                        <form action="{{ route('collections.removeMovie', [$collection, $movie]) }}" method="POST"
                            class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity z-30">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Koleksiyondan çıkar"
                                class="w-8 h-8 bg-red-500/80 backdrop-blur text-white rounded-lg flex items-center justify-center hover:bg-red-600 transition-all text-xs shadow-lg">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </div>
                @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ÇOK SEÇİMLİ FİLM EKLEME MODALI --}}
    <div id="addMovieModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" style="display: none;"
        onclick="if(event.target === this) closeAddMovieModal()">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl w-full max-w-2xl shadow-2xl" style="max-height: 85vh;">

                {{-- Modal Başlık --}}
                <div class="p-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-white text-xl font-bold flex items-center gap-3">
                            <div class="w-10 h-10 bg-teal-500/20 text-teal-400 rounded-xl flex items-center justify-center">
                                <i class="fas fa-plus"></i>
                            </div>
                            Koleksiyona Film Ekle
                        </h2>
                        <div id="selectedCount" class="bg-teal-600/20 text-teal-400 px-3 py-1.5 rounded-lg text-sm font-bold" style="display: none;">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span id="selectedCountNumber">0</span> film seçildi
                        </div>
                    </div>

                    {{-- Arama ve Filtre --}}
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500"></i>
                            <input type="text" id="movieSearch" placeholder="Film adı ile ara..."
                                class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl pl-10 pr-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-600 text-sm"
                                oninput="filterMovies(this.value)">
                        </div>
                        <button type="button" onclick="toggleSelectAll()"
                            class="bg-slate-800 border border-slate-700 text-slate-400 hover:text-white hover:border-teal-500 px-4 py-3 rounded-xl text-sm font-bold transition-all whitespace-nowrap">
                            <i class="fas fa-check-double mr-1"></i> Tümünü Seç
                        </button>
                    </div>
                </div>

                {{-- Film Listesi (SCROLL BURADA) --}}
                <form id="bulkAddForm" action="{{ route('collections.addMovies', $collection) }}" method="POST">
                    @csrf
                    <div id="movieList" style="max-height: 50vh; overflow-y: auto; -webkit-overflow-scrolling: touch;" class="px-6 space-y-1.5">
                        @php
                            $userMovies = Auth::user()->movies()->orderBy('title')->get();
                            $existingIds = $collection->movies->pluck('id')->toArray();
                        @endphp

                        @foreach($userMovies as $movie)
                            @if(!in_array($movie->id, $existingIds))
                                <label class="movie-item flex items-center gap-3 p-3 bg-slate-800/30 rounded-xl hover:bg-slate-800 transition-all cursor-pointer border-2 border-transparent has-[:checked]:border-teal-500/50 has-[:checked]:bg-teal-900/20">
                                    <input type="checkbox" name="movie_ids[]" value="{{ $movie->id }}"
                                        class="w-5 h-5 rounded-lg border-slate-600 bg-slate-800 text-teal-500 focus:ring-teal-500 focus:ring-offset-0 cursor-pointer flex-shrink-0"
                                        onchange="updateSelectedCount()">

                                    @if($movie->poster_path)
                                        <img src="https://image.tmdb.org/t/p/w92{{ $movie->poster_path }}"
                                            class="w-10 h-14 rounded-lg object-cover flex-shrink-0 shadow-md">
                                    @else
                                        <div class="w-10 h-14 bg-slate-700 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-image text-slate-600 text-xs"></i>
                                        </div>
                                    @endif

                                    <div class="flex-1 min-w-0">
                                        <p class="text-white font-bold text-sm truncate movie-title">{{ $movie->title }}</p>
                                        <div class="flex items-center gap-2 text-slate-500 text-xs mt-0.5">
                                            <span>{{ $movie->release_date ? substr($movie->release_date, 0, 4) : '' }}</span>
                                            @if($movie->director)
                                                <span class="text-slate-700">•</span>
                                                <span class="truncate">{{ $movie->director }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($movie->rating)
                                        <div class="text-xs font-bold text-yellow-400 flex items-center gap-1 flex-shrink-0">
                                            <i class="fas fa-star text-[10px]"></i> {{ number_format($movie->rating, 1) }}
                                        </div>
                                    @endif
                                </label>
                            @endif
                        @endforeach
                    </div>

                    {{-- Alt Butonlar --}}
                    <div class="p-6 pt-4 flex gap-3 border-t border-slate-800">
                        <button type="submit" id="addSelectedBtn"
                            class="flex-1 bg-teal-600 hover:bg-teal-500 disabled:bg-slate-800 disabled:text-slate-600 text-white py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2" disabled>
                            <i class="fas fa-plus-circle"></i> Seçilenleri Ekle
                        </button>
                        <button type="button" onclick="closeAddMovieModal()"
                            class="px-6 bg-slate-800 text-slate-400 hover:text-white py-3 rounded-xl font-bold transition-all border border-slate-700">
                            İptal
                        </button>
                    </div>
                </form>
            </div>
    </div>
@push('scripts')
<script>
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

@push('scripts')
<script>
    // ─── MODAL KONTROLÜ ───
    function openAddMovieModal() {
        const modal = document.getElementById('addMovieModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeAddMovieModal() {
        const modal = document.getElementById('addMovieModal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    // ─── CHECKBOX SEÇİM SAYACI ───
    function updateSelectedCount() {
        const checked = document.querySelectorAll('#bulkAddForm input[type="checkbox"]:checked');
        const countEl = document.getElementById('selectedCount');
        const countNum = document.getElementById('selectedCountNumber');
        const btn = document.getElementById('addSelectedBtn');

        countNum.textContent = checked.length;

        if (checked.length > 0) {
            countEl.classList.remove('hidden');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus-circle"></i> ' + checked.length + ' Film Ekle';
        } else {
            countEl.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-plus-circle"></i> Seçilenleri Ekle';
        }
    }

    // ─── TÜMÜNÜ SEÇ / TEMİZLE ───
    function toggleSelectAll() {
        const checkboxes = document.querySelectorAll('#bulkAddForm input[type="checkbox"]');
        // Sadece görünür olanları say
        const visibleCheckboxes = [...checkboxes].filter(cb => cb.closest('.movie-item').style.display !== 'none');
        const allChecked = visibleCheckboxes.every(cb => cb.checked);

        visibleCheckboxes.forEach(cb => cb.checked = !allChecked);
        updateSelectedCount();
    }

    // ─── FİLM ARAMA ───
    function filterMovies(query) {
        const items = document.querySelectorAll('.movie-item');
        const lowerQuery = query.toLowerCase();
        items.forEach(item => {
            const title = item.querySelector('.movie-title').textContent.toLowerCase();
            item.style.display = title.includes(lowerQuery) ? 'flex' : 'none';
        });
    }

    // ─── SÜRÜKLE BIRAK (DRAG & DROP) ───
    function handleDragOver(e) {
        e.preventDefault();
        e.currentTarget.classList.add('border-teal-500', 'border-2', 'border-dashed');
        e.currentTarget.style.backgroundColor = 'rgba(20, 184, 166, 0.05)';
    }

    function handleDragLeave(e) {
        e.currentTarget.classList.remove('border-teal-500', 'border-2', 'border-dashed');
        e.currentTarget.style.backgroundColor = '';
    }

    function handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('border-teal-500', 'border-2', 'border-dashed');
        e.currentTarget.style.backgroundColor = '';

        const movieId = e.dataTransfer.getData('text/movie-id');
        if (movieId) {
            // AJAX ile film ekle
            fetch('{{ route("collections.addMovie", $collection) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ movie_id: parseInt(movieId) })
            }).then(response => {
                if (response.ok) {
                    window.location.reload();
                }
            });
        }
    }

    // ─── KOLEKSİYON İÇİ SIRALAMA (DRAG & DROP) ───
    let draggedCollectionMovieId = null;

    function handleCollectionDragStart(e) {
        draggedCollectionMovieId = e.currentTarget.dataset.collectionMovieId;
        e.dataTransfer.effectAllowed = 'move';
        e.currentTarget.classList.add('opacity-50');
    }

    function handleCollectionDragEnd(e) {
        e.currentTarget.classList.remove('opacity-50');
        document.querySelectorAll('.collection-movie-item').forEach(el => {
            el.classList.remove('ring-2', 'ring-teal-500');
        });
    }

    function handleCollectionDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        e.currentTarget.classList.add('ring-2', 'ring-teal-500');
    }

    async function handleCollectionDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('ring-2', 'ring-teal-500');

        const targetId = e.currentTarget.dataset.collectionMovieId;
        if (!draggedCollectionMovieId || !targetId || draggedCollectionMovieId === targetId) return;

        const grid = document.getElementById('collectionGrid');
        const draggedEl = grid.querySelector(`[data-collection-movie-id="${draggedCollectionMovieId}"]`);
        const targetEl = grid.querySelector(`[data-collection-movie-id="${targetId}"]`);
        if (!draggedEl || !targetEl) return;

        const nodes = [...grid.querySelectorAll('.collection-movie-item')];
        const draggedIndex = nodes.indexOf(draggedEl);
        const targetIndex = nodes.indexOf(targetEl);

        if (draggedIndex < targetIndex) {
            targetEl.after(draggedEl);
        } else {
            targetEl.before(draggedEl);
        }

        const orderedIds = [...grid.querySelectorAll('.collection-movie-item')]
            .map(el => parseInt(el.dataset.collectionMovieId, 10));

        try {
            const response = await fetch('{{ route("collections.reorderMovies", $collection) }}', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ movie_ids: orderedIds })
            });

            if (!response.ok) {
                window.location.reload();
            }
        } catch (error) {
            window.location.reload();
        }
    }

    function toggleSelectAllCollectionMovies() {
        const checkboxes = document.querySelectorAll('.collection-bulk-checkbox');
        const allChecked = [...checkboxes].length > 0 && [...checkboxes].every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
    }

    function prepareBulkRemoveSubmission() {
        const selected = [...document.querySelectorAll('.collection-bulk-checkbox:checked')]
            .map(cb => parseInt(cb.value, 10));

        if (selected.length === 0) {
            alert('Önce koleksiyondan çıkarılacak filmleri seçmelisin.');
            return false;
        }

        const hiddenInput = document.getElementById('bulk-remove-input');
        hiddenInput.value = JSON.stringify(selected);

        const form = hiddenInput.closest('form');
        selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'movie_ids[]';
            input.value = String(id);
            form.appendChild(input);
        });

        return confirm('Seçili filmleri koleksiyondan çıkarmak istediğine emin misin?');
    }
</script>
@endpush
