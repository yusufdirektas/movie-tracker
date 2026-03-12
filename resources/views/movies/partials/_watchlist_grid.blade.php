{{--
    📚 AJAX PARTİAL: İzleme Listesi Grid
    Bu partial hem ilk sayfa yüklemesinde @include ile hem de
    AJAX isteklerinde tek başına render edilir.
--}}
@if ($movies->isEmpty())
    <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
        <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
            <i class="fas fa-popcorn text-3xl text-slate-600"></i>
        </div>
        <h3 class="text-xl font-bold text-white mb-2">Listende hiç film yok.</h3>
        <p class="text-slate-500 mb-8 italic text-sm">İzlemek istediğin yeni filmleri keşfetme vakti!</p>
        <div class="flex justify-center gap-4 mt-6">
            <a href="{{ route('movies.create') }}"
                class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-xl font-bold transition-all inline-block shadow-lg shadow-indigo-600/20">
                + Film Ara ve Ekle
            </a>
        </div>
    </div>
@else
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
        @foreach ($movies as $movie)
            <div class="relative">

                <div class="group relative bg-slate-900 rounded-3xl overflow-hidden border border-slate-800 transition-all hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/10">

                    <a href="{{ route('movies.show', $movie) }}" class="aspect-[2/3] relative overflow-hidden bg-slate-800 cursor-pointer block">
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
                            <span class="bg-amber-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENECEK</span>
                        </div>

                        {{-- ÇOKLU SEÇİM CHECKBOX'I --}}
                        <div class="absolute top-4 right-4 z-40">
                            <input type="checkbox" x-model="selectedMovies" value="{{ $movie->id }}" @click.stop
                                class="w-6 h-6 rounded-lg text-indigo-500 bg-black/60 border-2 border-white/20 focus:ring-indigo-500 focus:ring-offset-0 focus:ring-offset-transparent cursor-pointer transition-all hover:scale-110 hover:border-white/50 shadow-xl">
                        </div>
                    </a>

                    <div class="p-5">
                        <a href="{{ route('movies.show', $movie) }}" class="text-white font-bold truncate mb-3 block hover:text-indigo-400 transition-colors" title="{{ $movie->title }}">
                            {{ $movie->title }}
                        </a>

                        <p class="text-indigo-400/80 text-[10px] font-bold uppercase tracking-wider mb-2 truncate"
                            title="{{ $movie->director ?? 'Bilinmiyor' }}">
                            <i class="fas fa-bullhorn mr-1"></i> {{ $movie->director ?? 'Bilinmiyor' }}
                        </p>

                        <div class="flex justify-between items-center mt-2 border-t border-slate-800/50 pt-3">
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

    {{-- SAYFALANDIRMA (PAGINATION) LİNKLERİ --}}
    <div class="mt-12 mb-24">
        {{ $movies->links() }}
    </div>
@endif
