@extends('layouts.app')

@section('title', 'Film İstatistiklerim')

@section('content')
<div class="container mx-auto"
     x-data="{
        searchTerm: '',
        // Esnek Arama Mantığı (Senin tercih ettiğin yapı)
        turkishToEnglish(text) {
            return text.toString().toLowerCase()
                .replace(/ğ/g, 'g')
                .replace(/ü/g, 'u')
                .replace(/ş/g, 's')
                .replace(/ı/g, 'i')
                .replace(/ö/g, 'o')
                .replace(/ç/g, 'c')
                .trim();
        }
     }">

    <div class="mb-12">
        <div class="flex flex-col md:flex-row justify-between items-end mb-6">
            <h1 class="text-4xl font-extrabold text-white tracking-tight italic">
                Film <span class="text-indigo-500">Analizim</span>
            </h1>

            <a href="{{ route('movies.import') }}" class="group flex items-center gap-2 text-slate-500 hover:text-indigo-400 transition-colors mt-4 md:mt-0">
                <span class="text-xs font-bold uppercase tracking-widest border-b border-slate-700 group-hover:border-indigo-400 pb-0.5">Toplu Liste Yükle</span>
                <i class="fas fa-file-import"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

            <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex items-center gap-4 hover:border-indigo-500/30 transition-colors">
                <div class="w-12 h-12 bg-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400">
                    <i class="fas fa-video text-xl"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">Toplam Film</p>
                    <p class="text-2xl font-black text-white">{{ $totalMovies }}</p>
                </div>
            </div>

            <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex items-center gap-4 hover:border-emerald-500/30 transition-colors">
                <div class="w-12 h-12 bg-emerald-500/20 rounded-2xl flex items-center justify-center text-emerald-400">
                    <i class="fas fa-check-circle text-xl"></i>
                </div>
                <div>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-wider">İzlenen</p>
                    <p class="text-2xl font-black text-white">{{ $watchedCount }}</p>
                </div>
            </div>

            <div class="bg-slate-800/50 border border-slate-700 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex items-center gap-4 hover:border-amber-500/30 transition-colors">
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

            <div class="bg-gradient-to-br from-indigo-900/50 to-slate-900 border border-indigo-500/30 p-1 rounded-3xl shadow-lg relative overflow-hidden group">
                @if($highestRated)
                    <div class="absolute inset-0 bg-cover bg-center opacity-20 group-hover:opacity-40 transition-opacity duration-500" style="background-image: url('https://image.tmdb.org/t/p/w500{{ $highestRated->poster_path }}')"></div>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                    <div class="relative p-5 h-full flex flex-col justify-center z-10">
                        <p class="text-indigo-300 text-[10px] font-bold uppercase tracking-widest mb-1 shadow-black drop-shadow-md">Zirvedeki Film</p>
                        <p class="text-white font-black truncate text-lg shadow-black drop-shadow-lg">{{ $highestRated->title }}</p>
                        <div class="flex items-center gap-1 text-yellow-400 text-sm font-bold mt-1 shadow-black drop-shadow-md">
                            <i class="fas fa-star"></i> {{ number_format($highestRated->rating, 1) }}
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

    <div class="mb-12">
        <div class="relative group max-w-md mx-auto md:mx-0">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-500 group-focus-within:text-indigo-500 transition-colors"></i>
            </div>
            <input type="text"
                   x-model="searchTerm"
                   placeholder="Film ismine göre filtrele..."
                   class="block w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-slate-600 shadow-xl"
            >
            <button x-show="searchTerm !== ''" @click="searchTerm = ''" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors">
                <i class="fas fa-times-circle"></i>
            </button>
        </div>
    </div>

    @if($movies->isEmpty())
        <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
            <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                <i class="fas fa-film text-3xl text-slate-600"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Henüz film eklenmemiş</h3>
            <p class="text-slate-500 mb-8 italic text-sm">İstatistiklerin oluşması için yeni filmler eklemelisin.</p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('movies.create') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-xl font-bold transition-all inline-block shadow-lg shadow-indigo-600/20">
                    + Film Ekle
                </a>
                <a href="{{ route('movies.import') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 px-8 py-4 rounded-xl font-bold transition-all inline-block">
                    Toplu Yükle
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
            @foreach($movies as $movie)
                <div x-data="{ modalOpen: false }"
                     x-show="searchTerm === '' || turkishToEnglish('{{ $movie->title }}').startsWith(turkishToEnglish(searchTerm))"
                     x-transition:enter="transition ease-out duration-300"
                     class="relative">

                    <div @click="modalOpen = true" class="group cursor-pointer relative bg-slate-900 rounded-3xl overflow-hidden border border-slate-800 transition-all hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/10">
                        <div class="aspect-[2/3] relative overflow-hidden bg-slate-800">
                            @if($movie->poster_path)
                                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950"><i class="fas fa-image text-4xl"></i></div>
                            @endif

                            @if($movie->rating)
                            <div class="absolute top-4 left-4 z-10">
                                <div class="bg-black/70 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10 shadow-lg">
                                    <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                    <span class="text-xs font-black">{{ number_format($movie->rating, 1) }}</span>
                                </div>
                            </div>
                            @endif

                            <div class="absolute top-4 right-4 z-10">
                                @if($movie->is_watched)
                                    <span class="bg-emerald-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENDİ</span>
                                @else
                                    <span class="bg-amber-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENECEK</span>
                                @endif
                            </div>
                        </div>
                        <div class="p-5">
                            <h4 class="text-white font-bold truncate mb-1" title="{{ $movie->title }}">{{ $movie->title }}</h4>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500 text-xs font-semibold">{{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}</span>
                                @if($movie->runtime)
                                    <span class="text-slate-400 text-[10px] font-mono bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">{{ $movie->runtime }} dk</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <template x-teleport="body">
                        <div x-show="modalOpen"
                             class="fixed inset-0 z-[99] flex items-center justify-center px-4 bg-slate-950/90 backdrop-blur-sm"
                             @keydown.escape.window="modalOpen = false"
                             x-transition.opacity
                             style="display: none;">

                            <div @click.away="modalOpen = false" class="bg-slate-900 border border-slate-800 w-full max-w-3xl rounded-[2.5rem] overflow-hidden shadow-2xl relative">
                                <button @click="modalOpen = false" class="absolute top-6 right-6 z-20 bg-slate-800/50 hover:bg-slate-800 text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>

                                <div class="flex flex-col md:flex-row">
                                    <div class="w-full md:w-2/5 aspect-[2/3]">
                                        <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="w-full md:w-3/5 p-8 md:p-12 flex flex-col justify-center">
                                        <h2 class="text-4xl font-black text-white mb-3 leading-tight">{{ $movie->title }}</h2>

                                        <div class="flex items-center gap-4 mb-8 flex-wrap">
                                            <span class="bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-lg font-bold text-xs uppercase border border-indigo-500/20">{{ $movie->release_date }}</span>
                                            <div class="flex items-center gap-1.5 text-yellow-400"><i class="fas fa-star text-base"></i><span class="text-lg font-black">{{ number_format($movie->rating, 1) }}</span></div>
                                            @if($movie->runtime)
                                            <div class="flex items-center gap-1.5 text-slate-400 text-xs font-bold bg-slate-800 px-2 py-1 rounded-lg"><i class="fas fa-clock"></i> <span>{{ $movie->runtime }} dk</span></div>
                                            @endif
                                        </div>

                                        <h3 class="text-slate-500 font-black mb-3 uppercase text-[10px] tracking-widest">Özet</h3>
                                        <p class="text-slate-300 leading-relaxed text-base italic max-h-40 overflow-y-auto custom-scrollbar pr-2">{{ $movie->overview ?? 'Özet yok.' }}</p>

                                        <div class="mt-10 pt-8 border-t border-slate-800 flex flex-wrap gap-4">
                                            <form action="{{ route('movies.update', $movie) }}" method="POST">
                                                @csrf @method('PATCH')
                                                <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-2xl text-sm font-black transition-all shadow-lg shadow-indigo-600/20">
                                                    {{ $movie->is_watched ? 'İzlemedim Olarak İşaretle' : 'İzledim Olarak İşaretle' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('movies.destroy', $movie) }}" method="POST" onsubmit="return confirm('Bu filmi arşivinizden silmek istediğinize emin misiniz?')">
                                                @csrf @method('DELETE')
                                                <button class="bg-slate-800 hover:bg-red-600/20 hover:text-red-500 text-slate-400 px-6 py-3 rounded-2xl text-sm font-black transition-all"><i class="fas fa-trash-alt mr-2"></i> Sil</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            @endforeach
        </div>

        <div x-show="searchTerm !== '' && $el.parentElement.querySelectorAll('[x-show*=\'searchTerm\']:not([style*=\'display: none\'])').length === 0" style="display: none;" class="text-center py-24">
            <div class="bg-slate-900 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-800">
                <i class="fas fa-search-minus text-4xl text-slate-700"></i>
            </div>
            <p class="text-slate-500 text-lg italic">Sonuç bulunamadı.</p>
        </div>
    @endif
</div>
@endsection
