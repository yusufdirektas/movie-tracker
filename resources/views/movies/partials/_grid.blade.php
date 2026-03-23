{{--
    📚 AJAX PARTİAL: Film Arşivi Grid
    Bu partial hem ilk sayfa yüklemesinde @include ile hem de
    AJAX isteklerinde tek başına render edilir.
    Böylece filtre/sıralama değişikliklerinde tüm sayfa yenilenmez.
--}}
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

                        {{--
                            📚 COMPONENT KULLANIMI
                            Eski: <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}" ...>
                            Yeni: <x-movie-poster ... />

                            Avantajları:
                            - Lazy loading otomatik
                            - Skeleton animasyonu var
                            - Hata yönetimi var
                            - Tek yerden güncelleme
                        --}}
                        <x-movie-poster
                            :path="$movie->poster_path"
                            :alt="$movie->title"
                            size="w500"
                            class="relative z-10"
                        />

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
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ personal_rating: star })
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
