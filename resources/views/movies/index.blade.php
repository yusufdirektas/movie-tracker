@extends('layouts.app')

@section('title', 'Film İstatistiklerim')

@section('content')
    {{-- Alpine.js State Wrapper: selectedMovies dizisi seçilen filmlerin ID'lerini tutar --}}
    <div class="container mx-auto" x-data="{ selectedMovies: [] }">

        <div class="mb-12">
            <div class="flex flex-col md:flex-row justify-between items-end mb-6">
                <h1 class="text-4xl font-extrabold text-white tracking-tight italic">
                    Film <span class="text-indigo-500">Analizim</span>
                </h1>

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

        {{-- BACKEND ARAMA ÇUBUĞU FORMU (YENİLENDİ) --}}
        <div class="mb-8">
            <form action="{{ route('movies.index') }}" method="GET" class="relative group max-w-md mx-auto md:mx-0">
                {{-- Filtre seçiliyse arama yaparken onu da aklında tutsun --}}
                <input type="hidden" name="filter" value="{{ request('filter', 'all') }}">

                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
                </div>

                {{-- Enter'a basınca arayacak --}}
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Arşivimde ara (Enter'a bas)..."
                    class="block w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-600 shadow-xl">

                {{-- Arama yapıldıysa çarpı butonu çıksın ve tıklayınca aramayı sıfırlasın --}}
                @if(request('search'))
                    <a href="{{ route('movies.index', ['filter' => request('filter', 'all')]) }}"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors">
                        <i class="fas fa-times-circle"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Yeni Şık Filtreleme Butonları --}}
        <div class="mb-8 flex justify-center md:justify-start">
            <div class="inline-flex bg-slate-900/80 p-1.5 rounded-2xl border border-slate-800 shadow-inner">

               {{-- Tümü --}}
    <a href="{{ route('movies.index', ['filter' => 'all']) }}"
       role="tab"
       aria-selected="{{ request('filter', 'all') === 'all' ? 'true' : 'false' }}"
       class="filter-btn {{ request('filter', 'all') === 'all' ? 'active-all active' : '' }}">
      <span class="btn-icon"><i class="fas fa-layer-group"></i></span>
      <span class="btn-label">Tümü</span>
      {{-- Opsiyonel sayaç: <span class="filter-badge">128</span> --}}
    </a>

    <div class="filter-divider" aria-hidden="true"></div>

    {{-- Favorilerim --}}
    <a href="{{ route('movies.index', ['filter' => 'favorites']) }}"
       role="tab"
       aria-selected="{{ request('filter') === 'favorites' ? 'true' : 'false' }}"
       class="filter-btn {{ request('filter') === 'favorites' ? 'active-favorites active' : '' }}">
      <span class="btn-icon"><i class="fas fa-heart"></i></span>
      <span class="btn-label">Favorilerim</span>
    </a>

            </div>
        </div>

        {{-- 📚 SIRALAMA DROPDOWN'I --}}
        <div class="mb-8 flex flex-col md:flex-row justify-between items-center gap-4">
            <form action="{{ route('movies.index') }}" method="GET" class="flex items-center gap-3">
                <input type="hidden" name="filter" value="{{ request('filter', 'all') }}">
                @if(request('search'))
                    <input type="hidden" name="search" value="{{ request('search') }}">
                @endif


                <span class="text-slate-500 text-xs font-bold uppercase tracking-widest hidden sm:inline-block">
                    <i class="fas fa-sort mr-1"></i> Sırala
                </span>
                <select name="sort" onchange="this.form.submit()"
                    class="bg-slate-900 border border-slate-700 text-white text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                    <option value="updated_at" {{ $sort === 'updated_at' ? 'selected' : '' }}>Son Eklenen</option>
                    <option value="title" {{ $sort === 'title' ? 'selected' : '' }}>İsme Göre (A-Z)</option>
                    <option value="rating" {{ $sort === 'rating' ? 'selected' : '' }}>TMDB Puanı</option>
                    <option value="personal_rating" {{ $sort === 'personal_rating' ? 'selected' : '' }}>Kişisel Puan</option>
                    <option value="release_date" {{ $sort === 'release_date' ? 'selected' : '' }}>Yayın Tarihi</option>
                    <option value="runtime" {{ $sort === 'runtime' ? 'selected' : '' }}>Süre</option>
                </select>

                <span class="text-slate-500 text-xs font-bold uppercase tracking-widest hidden sm:inline-block ml-4">
                    <i class="fas fa-filter mr-1"></i> Tür
                </span>
                <select name="genre" onchange="this.form.submit()"
                    class="bg-slate-900 border border-slate-700 text-white text-sm rounded-xl px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer max-w-[150px] truncate">
                    <option value="">Tüm Türler</option>
                    @foreach($availableGenres as $g)
                        <option value="{{ $g }}" {{ request('genre') === $g ? 'selected' : '' }}>
                            {{ $g }}
                        </option>
                    @endforeach
                </select>
            </form>

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
        @if ($movies->isEmpty())
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
                <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-film text-3xl text-slate-600"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Henüz film eklenmemiş veya bulunamadı.</h3>
                <div class="flex justify-center gap-4 mt-6">
                    <a href="{{ route('movies.create') }}"
                        class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-xl font-bold transition-all inline-block shadow-lg shadow-indigo-600/20">
                        + Film Ekle
                    </a>
                    <a href="{{ route('movies.index') }}"
                        class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-4 rounded-xl font-bold transition-all inline-block">
                        Filtreleri Temizle
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
                @foreach ($movies as $movie)
                    <div class="relative" draggable="true"
                        ondragstart="event.dataTransfer.setData('text/movie-id', '{{ $movie->id }}'); this.style.opacity='0.5';"
                        ondragend="this.style.opacity='1';">

                        <div class="group relative bg-slate-900 rounded-3xl overflow-hidden border border-slate-800 transition-all hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/10">

                            <a href="{{ route('movies.show', $movie) }}"
                                class="aspect-[2/3] relative overflow-hidden bg-slate-800 cursor-pointer block">

                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-all z-20 flex items-center justify-center">
                                    <i class="fas fa-search-plus opacity-0 group-hover:opacity-100 text-white text-5xl drop-shadow-lg scale-50 group-hover:scale-100 transition-all duration-300"></i>
                                </div>

                                @if ($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                        class="w-full h-full object-cover relative z-10" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950 relative z-10">
                                        <i class="fas fa-image text-4xl"></i>
                                    </div>
                                @endif

                                @if ($movie->rating)
                                    <div class="absolute top-4 left-4 z-30 pointer-events-none">
                                        <div class="bg-black/70 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10 shadow-lg">
                                            <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                            <span class="text-xs font-black">{{ number_format($movie->rating, 1) }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="absolute bottom-4 left-4 z-30 pointer-events-none">
                                    @if ($movie->is_watched)
                                        <span class="bg-emerald-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENDİ</span>
                                    @else
                                        <span class="bg-amber-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENECEK</span>
                                    @endif
                                </div>

                                {{-- ÇOKLU SEÇİM CHECKBOX'I --}}
                                <div class="absolute top-4 right-4 z-40">
                                    <input type="checkbox" x-model="selectedMovies" value="{{ $movie->id }}" @click.stop
                                        class="w-6 h-6 rounded-lg text-indigo-500 bg-black/60 border-2 border-white/20 focus:ring-indigo-500 focus:ring-offset-0 focus:ring-offset-transparent cursor-pointer transition-all hover:scale-110 hover:border-white/50 shadow-xl">
                                </div>
                            </a>

                            <div class="p-5">
                                <a href="{{ route('movies.show', $movie) }}" class="text-white font-bold truncate mb-0.5 block hover:text-indigo-400 transition-colors" title="{{ $movie->title }}">
                                    {{ $movie->title }}</a>
                                <div x-data="{
                                    rating: {{ $movie->personal_rating ?? 0 }},
                                    hoverRating: 0,
                                    saveRating(star) {
                                        this.rating = star;
                                        fetch('/movies/{{ $movie->id }}', {
                                            method: 'PUT',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            },
                                            body: JSON.stringify({ personal_rating: star })
                                        }).then(() => {
                                            window.location.reload();
                                        });
                                    }
                                }" class="flex flex-col gap-1 mt-3 pt-3 border-t border-slate-800/50">

                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Puanım</span>
                                        @if ($movie->watched_at)
                                            <span class="text-[10px] text-emerald-500 font-bold"><i class="fas fa-calendar-check mr-1"></i>{{ $movie->watched_at->format('d.m.Y') }}</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-1">
                                        <template x-for="star in 5">
                                            <button type="button" @click.stop.prevent="saveRating(star)"
                                                @mouseenter="hoverRating = star" @mouseleave="hoverRating = 0"
                                                class="focus:outline-none transition-transform hover:scale-125">
                                                <i class="fas fa-star text-sm transition-colors duration-200"
                                                    :class="(hoverRating >= star || (!hoverRating && rating >= star)) ?
                                                    'text-yellow-400 drop-shadow-[0_0_5px_rgba(250,204,21,0.5)]' :
                                                    'text-slate-700 hover:text-slate-500'"></i>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <p class="text-indigo-400/80 text-[10px] font-bold uppercase tracking-wider mb-2 truncate"
                                    title="{{ $movie->director ?? 'Bilinmiyor' }}">
                                    <i class="fas fa-bullhorn mr-1"></i> {{ $movie->director ?? 'Bilinmiyor' }}
                                </p>

                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-slate-500 text-xs font-semibold">{{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}</span>
                                    @if ($movie->runtime)
                                        <span class="text-slate-400 text-[10px] font-mono bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">{{ $movie->runtime }} dk</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- SAYFALANDIRMA --}}
            <div class="mt-12 mb-24">
                {{ $movies->links() }}
            </div>
        @endif

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
