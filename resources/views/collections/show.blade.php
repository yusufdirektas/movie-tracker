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
                    <h1 class="text-4xl font-extrabold text-white tracking-tight">
                        {{ $collection->name }}
                    </h1>
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

        {{-- FİLMLER --}}
        @if($collection->movies->isEmpty())
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center"
                 id="dropZone"
                 ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-film text-4xl text-slate-600"></i>
                </div>
                <h3 class="text-white text-xl font-bold mb-2">Bu koleksiyon henüz boş</h3>
                <p class="text-slate-500 mb-6">"Film Ekle" butonuna tıklayarak veya <strong class="text-teal-400">Film Arşivim</strong> sayfasından filmleri sürükleyip bırakarak ekleyebilirsin.</p>
                <button onclick="openAddMovieModal()"
                    class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-3 rounded-xl font-bold transition-all">
                    <i class="fas fa-plus mr-2"></i> Film Ekle
                </button>
            </div>
        @else
            <div id="dropZone" class="w-full"
                 ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event)">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
                    @foreach($collection->movies as $movie)
                        <div class="group relative">
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
</script>
@endpush
