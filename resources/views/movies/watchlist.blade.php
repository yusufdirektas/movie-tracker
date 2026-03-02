@extends('layouts.app')

@section('title', 'İzleme Listem')

@section('content')
    <div class="container mx-auto" x-data="{
        searchTerm: '',
        turkishToEnglish(text) {
            return text.toString().toLocaleLowerCase('tr-TR')
                .replace(/ğ/g, 'g')
                .replace(/ü/g, 'u')
                .replace(/ş/g, 's')
                .replace(/ı/g, 'i')
                .replace(/ö/g, 'o')
                .replace(/ç/g, 'c')
                .trim();
        }
    }">

        {{-- Üst Başlık ve Bekleyen Film Sayısı --}}
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-4xl font-extrabold text-white tracking-tight italic">
                <i class="fas fa-hourglass-half text-amber-500 mr-2 text-3xl"></i> İzleme <span
                    class="text-amber-500">Listem</span>
            </h1>
            <span
                class="bg-slate-800 text-slate-300 px-4 py-2 rounded-xl text-sm font-bold border border-slate-700 shadow-lg">
                Bekleyen {{ $totalMovies }} Film
            </span>
        </div>

        {{-- Arama Kutusu --}}
        <div class="mb-12">
            <div class="relative group max-w-md">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fas fa-search text-slate-500 group-focus-within:text-amber-500 transition-colors"></i>
                </div>
                <input type="text" x-model="searchTerm" placeholder="İzleme listemde ara..."
                    class="block w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-2xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all placeholder-slate-600 shadow-xl">
                <button x-show="searchTerm !== ''" @click="searchTerm = ''"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white transition-colors"
                    style="display: none;">
                    <i class="fas fa-times-circle"></i>
                </button>
            </div>
        </div>

        @if ($movies->isEmpty())
            {{-- Liste Boşsa Görünecek Ekran --}}
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center shadow-xl">
                <div
                    class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner border border-slate-700">
                    <i class="fas fa-ticket-alt text-3xl text-slate-500"></i>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">İzleme listen bomboş!</h3>
                <p class="text-slate-500 mb-8 italic text-sm">Hemen yeni filmler keşfet ve listene ekle.</p>
                <a href="{{ route('movies.create') }}"
                    class="bg-amber-500 hover:bg-amber-400 text-slate-900 px-8 py-4 rounded-xl font-black transition-all inline-block shadow-lg shadow-amber-500/20">
                    <i class="fas fa-plus mr-2"></i> Film Ekle
                </a>
            </div>
        @else
            {{-- Film Kartları Grid Alanı --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-8">
                @foreach ($movies as $movie)
                    <div x-data="{ modalOpen: false }"
                        x-show="searchTerm === '' || turkishToEnglish(@js($movie->title)).includes(turkishToEnglish(searchTerm))"
                        x-transition:enter="transition ease-out duration-300" class="relative">

                        {{-- 1. DÜZELTME: Tıklama komutunu ana kutudan SİLDİK --}}
                        <div
                            class="group relative bg-slate-900 rounded-3xl overflow-hidden border border-slate-800 transition-all hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/10">

                            {{-- 2. DÜZELTME: Tıklama komutunu SADECE afiş alanına EKLEDİK --}}
                            <div @click="modalOpen = true"
                                class="aspect-[2/3] relative overflow-hidden bg-slate-800 cursor-pointer">

                                {{-- Ekstra: Tıklanabilir olduğunu belli eden şık bir hover efekti --}}
                                <div
                                    class="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-all z-20 flex items-center justify-center">
                                    <i
                                        class="fas fa-search-plus opacity-0 group-hover:opacity-100 text-white text-5xl drop-shadow-lg scale-50 group-hover:scale-100 transition-all duration-300"></i>
                                </div>

                                @if ($movie->poster_path)
                                    <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                        class="w-full h-full object-cover relative z-10" loading="lazy">
                                @else
                                    <div
                                        class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950 relative z-10">
                                        <i class="fas fa-image text-4xl"></i>
                                    </div>
                                @endif

                                @if ($movie->rating)
                                    <div class="absolute top-4 left-4 z-30">
                                        <div
                                            class="bg-black/70 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10 shadow-lg">
                                            <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                            <span class="text-xs font-black">{{ number_format($movie->rating, 1) }}</span>
                                        </div>
                                    </div>
                                @endif

                                <div class="absolute top-4 right-4 z-30">
                                    @if ($movie->is_watched)
                                        <span
                                            class="bg-emerald-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENDİ</span>
                                    @else
                                        <span
                                            class="bg-amber-500/90 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg italic">İZLENECEK</span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-5">
                                <h4 class="text-white font-bold truncate mb-0.5" title="{{ $movie->title }}">
                                    {{ $movie->title }}</h4>
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
                                }"
                                    class="flex flex-col gap-1 mt-3 pt-3 border-t border-slate-800/50">

                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-[10px] text-slate-500 font-bold uppercase tracking-widest">Puanım</span>

                                        @if ($movie->watched_at)
                                            <span class="text-[10px] text-emerald-500 font-bold"><i
                                                    class="fas fa-calendar-check mr-1"></i>{{ $movie->watched_at->format('d.m.Y') }}</span>
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
                                    <span
                                        class="text-slate-500 text-xs font-semibold">{{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}</span>
                                    @if ($movie->runtime)
                                        <span
                                            class="text-slate-400 text-[10px] font-mono bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700">{{ $movie->runtime }}
                                            dk</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- YENİLENMİŞ MODAL İÇERİĞİ --}}
                        {{-- MODAL İÇERİĞİ --}}
                        {{-- MODAL İÇERİĞİ --}}
                        {{-- MODAL İÇERİĞİ --}}
                        {{-- MODAL İÇERİĞİ --}}
                        {{-- MODAL İÇERİĞİ --}}
                        <template x-teleport="body">
                            {{-- ARKA PLAN: p-4 ve items-center eklenerek her ekranda "Pencere" görünümü garantilendi --}}
                            <div x-show="modalOpen"
                                class="fixed inset-0 z-[99] flex items-center justify-center p-4 md:p-6 bg-slate-950/90 backdrop-blur-sm"
                                @keydown.escape.window="modalOpen = false" x-transition.opacity style="display: none;">

                                {{-- ANA PENCERE: Yuvarlak köşeler, gölge ve maksimum yükseklik --}}
                                <div @click.away="modalOpen = false"
                                    class="bg-slate-900 border border-slate-800 w-full max-w-3xl max-h-[90vh] flex flex-col rounded-[2rem] md:rounded-[2.5rem] overflow-hidden shadow-2xl relative">

                                    {{-- Kapat Butonu --}}
                                    <button @click="modalOpen = false"
                                        class="absolute top-4 right-4 z-50 bg-slate-900/80 backdrop-blur-sm hover:bg-slate-800 text-white w-10 h-10 rounded-full flex items-center justify-center transition-colors border border-slate-700 shadow-xl">
                                        <i class="fas fa-times"></i>
                                    </button>

                                    {{-- KAYDIRILABİLİR İÇERİK BÖLÜMÜ --}}
                                    <div class="overflow-y-auto custom-scrollbar flex-1 w-full">

                                        <div class="flex flex-col md:hidden">

                                            {{-- Afiş Kapak Fotoğrafı --}}
                                            <div class="relative w-full h-64 shrink-0 bg-slate-950">
                                                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                                    class="w-full h-full object-cover object-top">
                                                {{-- Resimden arka plana yumuşak geçiş --}}
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent">
                                                </div>
                                            </div>

                                            {{-- Film Bilgileri (Afişin üstüne hafifçe taşar) --}}
                                            <div class="px-6 pb-6 -mt-8 relative z-10">
                                                <h2 class="text-3xl font-black text-white leading-tight mb-2 pr-8">
                                                    {{ $movie->title }}</h2>

                                                <p
                                                    class="text-indigo-400 text-sm font-bold tracking-widest uppercase mb-4 flex items-center gap-2">
                                                    <i class="fas fa-video"></i>
                                                    {{ $movie->director ?? 'Yönetmen Bilgisi Yok' }}
                                                </p>

                                                <div class="flex items-center gap-3 flex-wrap mb-6">
                                                    <span
                                                        class="bg-indigo-500/10 text-indigo-400 px-3 py-1.5 rounded-lg font-bold text-xs uppercase border border-indigo-500/20">
                                                        {{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}
                                                    </span>
                                                    <div
                                                        class="flex items-center gap-1.5 text-yellow-400 bg-yellow-400/10 px-3 py-1.5 rounded-lg border border-yellow-400/20">
                                                        <i class="fas fa-star text-xs"></i>
                                                        <span
                                                            class="text-sm font-black">{{ number_format($movie->rating ?? 0, 1) }}</span>
                                                    </div>
                                                    @if ($movie->runtime)
                                                        <div
                                                            class="flex items-center gap-1.5 text-slate-400 text-xs font-bold bg-slate-800 px-3 py-1.5 rounded-lg border border-slate-700">
                                                            <i class="fas fa-clock"></i> <span>{{ $movie->runtime }}
                                                                dk</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <h3
                                                    class="text-slate-500 font-black mb-2 uppercase text-[10px] tracking-widest">
                                                    Özet</h3>
                                                <p class="text-slate-300 leading-relaxed text-sm italic">
                                                    {{ $movie->overview ?? 'Özet yok.' }}</p>
                                            </div>
                                        </div>

                                        <div class="hidden md:flex flex-row h-full">

                                            <div class="w-2/5 shrink-0 bg-slate-950 relative">
                                                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                                    class="w-full h-full object-cover">
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-transparent to-slate-900/50">
                                                </div>
                                            </div>

                                            <div class="w-3/5 p-10 flex flex-col justify-center">
                                                <h2 class="text-4xl font-black text-white mb-2 leading-tight pr-8">
                                                    {{ $movie->title }}</h2>

                                                <p
                                                    class="text-indigo-400 text-sm font-bold tracking-widest uppercase mb-6 flex items-center gap-2 drop-shadow-md">
                                                    <i class="fas fa-video"></i>
                                                    {{ $movie->director ?? 'Yönetmen Bilgisi Yok' }}
                                                </p>

                                                <div class="flex items-center gap-4 mb-8 flex-wrap">
                                                    <span
                                                        class="bg-indigo-500/10 text-indigo-400 px-3 py-1 rounded-lg font-bold text-xs uppercase border border-indigo-500/20">{{ $movie->release_date }}</span>
                                                    <div
                                                        class="flex items-center gap-1.5 text-yellow-400 bg-slate-800 px-3 py-1 rounded-lg">
                                                        <i class="fas fa-star text-base"></i><span
                                                            class="text-lg font-black">{{ number_format($movie->rating ?? 0, 1) }}</span>
                                                    </div>
                                                    @if ($movie->runtime)
                                                        <div
                                                            class="flex items-center gap-1.5 text-slate-400 text-xs font-bold bg-slate-800 px-3 py-1 rounded-lg">
                                                            <i class="fas fa-clock"></i> <span>{{ $movie->runtime }}
                                                                dk</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <h3
                                                    class="text-slate-500 font-black mb-3 uppercase text-[10px] tracking-widest">
                                                    Özet</h3>
                                                <p class="text-slate-300 leading-relaxed text-base italic">
                                                    {{ $movie->overview ?? 'Özet yok.' }}</p>
                                            </div>
                                        </div>

                                    </div>

                                    <div
                                        class="bg-slate-900 border-t border-slate-800 p-4 md:p-6 shrink-0 flex flex-wrap gap-4 justify-start relative z-20">
                                        <form action="{{ route('movies.update', $movie) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button
                                                class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-2xl text-sm font-black transition-all shadow-lg shadow-indigo-600/20">
                                                {{ $movie->is_watched ? 'İzlemedim Olarak İşaretle' : 'İzledim Olarak İşaretle' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('movies.destroy', $movie) }}" method="POST"
                                            onsubmit="return confirm('Bu filmi arşivinizden silmek istediğinize emin misiniz?')">
                                            @csrf @method('DELETE')
                                            <button
                                                class="bg-slate-800 hover:bg-red-600/20 hover:text-red-500 text-slate-400 px-6 py-3 rounded-2xl text-sm font-black transition-all border border-slate-700">
                                                <i class="fas fa-trash-alt mr-2"></i> Sil
                                            </button>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>

            {{-- Arama Sonucu Bulunamadığında --}}
            <div x-show="searchTerm !== '' && $el.parentElement.querySelectorAll('[x-show*=\'searchTerm\']:not([style*=\'display: none\'])').length === 0"
                style="display: none;" class="text-center py-24">
                <div
                    class="bg-slate-900 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6 border border-slate-800">
                    <i class="fas fa-search-minus text-4xl text-slate-700"></i>
                </div>
                <p class="text-slate-500 text-lg italic">Sonuç bulunamadı.</p>
            </div>
        @endif
    </div>
@endsection
