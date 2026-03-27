{{--
    📚 BLADE TEMPLATE SİSTEMİ:

    @extends('layouts.app')  → Bu view, layouts/app.blade.php şablonunu kullanır.
    @section('content')      → app.blade.php'deki @yield('content') yerine bu içerik yerleşir.

    Bu dosya "Film Detay Sayfası"dır. Kullanıcı bir filme tıkladığında
    buraya yönlendirilir: /movies/{id} → MovieController::show()

    📚 compact('movie', 'similarMovies'):
    Controller'dan gelen değişkenler burada {{ $movie->title }} gibi kullanılır.
--}}
@extends('layouts.app')

@section('title', $movie->title . ' — Film Detayı')

@section('content')
    <div class="container mx-auto">

        {{-- ÜST BÖLÜM: Film Bilgileri --}}
        <div class="relative bg-slate-900 rounded-[2.5rem] overflow-hidden border border-slate-800 shadow-2xl mb-12">

            {{-- Arka plan posteri (bulanık) --}}
            @if ($movie->poster_path)
                <div class="absolute inset-0 bg-cover bg-center opacity-10 blur-2xl scale-110"
                    style="background-image: url('https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}')">
                </div>
            @endif

            <div class="relative z-10 flex flex-col md:flex-row">

                {{-- SOL: Poster --}}
                <div class="md:w-1/3 lg:w-1/4 shrink-0">
                    <div class="aspect-[2/3] relative overflow-hidden md:rounded-l-[2.5rem]">
                        {{-- 📚 Component kullanımı - Detay sayfasında daha büyük resim (w780) --}}
                        <x-movie-poster
                            :path="$movie->poster_path"
                            :alt="$movie->title"
                            size="w780"
                        />

                        {{-- İzlendi/İzlenecek rozet --}}
                        <div class="absolute top-4 right-4 z-30">
                            @if ($movie->is_watched)
                                <span class="bg-emerald-500/90 text-white text-xs font-black px-4 py-1.5 rounded-full shadow-lg">
                                    <i class="fas fa-check mr-1"></i> İZLENDİ
                                </span>
                            @else
                                <span class="bg-amber-500/90 text-white text-xs font-black px-4 py-1.5 rounded-full shadow-lg">
                                    <i class="fas fa-clock mr-1"></i> İZLENECEK
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- SAĞ: Detaylar --}}
                <div class="flex-1 p-8 md:p-10 lg:p-12 flex flex-col justify-center">

                    {{-- Başlık --}}
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-black text-white leading-tight mb-3">
                        {{ $movie->title }}
                    </h1>

                    {{-- Yönetmen --}}
                    <p class="text-indigo-400 text-sm font-bold tracking-widest uppercase mb-6 flex items-center gap-2">
                        <i class="fas fa-video"></i> {{ $movie->director ?? 'Yönetmen Bilgisi Yok' }}
                    </p>

                    {{-- Meta bilgiler --}}
                    <div class="flex items-center gap-3 flex-wrap mb-8">
                        {{-- Yayın Yılı --}}
                        <span class="bg-indigo-500/10 text-indigo-400 px-4 py-2 rounded-xl font-bold text-sm border border-indigo-500/20">
                            <i class="fas fa-calendar mr-1"></i>
                            {{ $movie->release_date ? substr($movie->release_date, 0, 4) : '-' }}
                        </span>

                        {{-- TMDB Puanı --}}
                        @if ($movie->rating)
                            <div class="flex items-center gap-1.5 text-yellow-400 bg-yellow-400/10 px-4 py-2 rounded-xl border border-yellow-400/20">
                                <i class="fas fa-star"></i>
                                <span class="font-black">{{ number_format($movie->rating, 1) }}</span>
                                <span class="text-yellow-400/60 text-xs ml-1">TMDB</span>
                            </div>
                        @endif

                        {{-- Süre --}}
                        @if ($movie->runtime)
                            <div class="flex items-center gap-1.5 text-slate-400 text-sm font-bold bg-slate-800 px-4 py-2 rounded-xl border border-slate-700">
                                <i class="fas fa-clock"></i>
                                <span>{{ floor($movie->runtime / 60) }}s {{ $movie->runtime % 60 }}dk</span>
                            </div>
                        @endif

                        {{-- İzlenme tarihi --}}
                        @if ($movie->watched_at)
                            <div class="flex items-center gap-1.5 text-emerald-400 text-sm font-bold bg-emerald-500/10 px-4 py-2 rounded-xl border border-emerald-500/20">
                                <i class="fas fa-calendar-check"></i>
                                <span>{{ $movie->watched_at->format('d.m.Y') }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Türler --}}
                    @if ($movie->genres && count($movie->genres) > 0)
                        <div class="flex flex-wrap gap-2 mb-8">
                            @foreach ($movie->genres as $genre)
                                <span class="bg-slate-800 text-slate-300 text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-700">
                                    {{ $genre }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Kişisel Puan --}}
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
                            }).then(() => window.location.reload());
                        }
                    }" class="mb-8">
                        <span class="text-slate-500 text-xs font-bold uppercase tracking-widest block mb-2">Kişisel Puanım</span>
                        <div class="flex items-center gap-2">
                            <template x-for="star in 5">
                                <button type="button" @click.stop.prevent="saveRating(star)"
                                    @mouseenter="hoverRating = star" @mouseleave="hoverRating = 0"
                                    class="focus:outline-none transition-transform hover:scale-125">
                                    <i class="fas fa-star text-2xl transition-colors duration-200"
                                        :class="(hoverRating >= star || (!hoverRating && rating >= star)) ?
                                        'text-yellow-400 drop-shadow-[0_0_8px_rgba(250,204,21,0.5)]' :
                                        'text-slate-700 hover:text-slate-500'"></i>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Özet --}}
                    @if ($movie->overview)
                        <div class="mb-8">
                            <h3 class="text-slate-500 font-black mb-3 uppercase text-[10px] tracking-widest">Film Özeti</h3>
                            <p class="text-slate-300 leading-relaxed text-base">{{ $movie->overview }}</p>
                        </div>
                    @endif

                    {{-- Hızlı Notlar --}}
                    <div class="mb-8">
                        <h3 class="text-slate-500 font-black mb-3 uppercase text-[10px] tracking-widest">Hızlı Notlarım</h3>
                        <form action="{{ route('movies.update', $movie) }}" method="POST" class="space-y-3">
                            @csrf
                            @method('PATCH')
                            <textarea
                                name="personal_note"
                                rows="4"
                                maxlength="1000"
                                placeholder="Bu film hakkında kısa notlarını yaz..."
                                class="w-full bg-slate-900/80 border border-slate-700 rounded-2xl px-4 py-3 text-slate-200 placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">{{ old('personal_note', $movie->personal_note) }}</textarea>

                            @error('personal_note')
                                <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror

                            <div class="flex items-center justify-between">
                                <span class="text-[11px] text-slate-500">Maksimum 1000 karakter</span>
                                <button type="submit"
                                    class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-4 py-2 rounded-xl text-xs font-bold transition border border-slate-600">
                                    <i class="fas fa-sticky-note mr-1"></i> Notu Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Aksiyon Butonları --}}
                    <div class="flex flex-wrap gap-4 items-start">
                        <form action="{{ route('movies.update', $movie) }}" method="POST">
                            @csrf @method('PATCH')
                            <button class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-2xl text-sm font-black transition-all shadow-lg shadow-indigo-600/20">
                                <i class="fas {{ $movie->is_watched ? 'fa-eye-slash' : 'fa-eye' }} mr-2"></i>
                                {{ $movie->is_watched ? 'İzlemedim Olarak İşaretle' : 'İzledim Olarak İşaretle' }}
                            </button>
                        </form>

                        {{-- Koleksiyona Ekle Dropdown --}}
                        <div x-data="{
                                open: false,
                                addingCollectionId: null,
                                addedCollectionIds: @json($movieCollectionIds),
                                toast: { show: false, type: 'success', message: '' },
                                isAdded(collectionId) {
                                    return this.addedCollectionIds.includes(collectionId);
                                },
                                showToast(type, message) {
                                    this.toast = { show: true, type, message };
                                    setTimeout(() => this.toast.show = false, 2200);
                                },
                                async addToCollection(collectionId) {
                                    if (this.isAdded(collectionId) || this.addingCollectionId !== null) return;
                                    this.addingCollectionId = collectionId;

                                    try {
                                        const response = await fetch(`/collections/${collectionId}/movies`, {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json',
                                            },
                                            body: JSON.stringify({ movie_id: {{ $movie->id }} }),
                                        });

                                        const data = await response.json().catch(() => ({}));

                                        if (response.ok) {
                                            this.addedCollectionIds.push(collectionId);
                                            this.showToast('success', data.message || 'Film koleksiyona eklendi!');
                                        } else {
                                            this.showToast('error', data.message || 'Koleksiyona eklenemedi.');
                                        }
                                    } catch (error) {
                                        this.showToast('error', 'Bir bağlantı hatası oluştu.');
                                    } finally {
                                        this.addingCollectionId = null;
                                    }
                                }
                            }"
                            class="relative">
                            <button @click="open = !open" type="button"
                                class="bg-teal-600/20 hover:bg-teal-600 text-teal-400 hover:text-white px-6 py-3 rounded-2xl text-sm font-black transition-all border border-teal-500/30 flex items-center gap-2">
                                <i class="fas fa-layer-group"></i> Koleksiyona Ekle
                                <i class="fas fa-chevron-down text-xs transition-transform" :class="open && 'rotate-180'"></i>
                            </button>

                            <div x-show="open" @click.away="open = false" x-transition
                                class="absolute left-0 bottom-full mb-2 w-72 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl z-50 overflow-hidden">

                                @if($collections->isEmpty())
                                    <div class="p-4 text-center">
                                        <p class="text-slate-500 text-sm mb-3">Henüz koleksiyon oluşturmadın</p>
                                        <a href="{{ route('collections.index') }}"
                                            class="text-teal-400 hover:text-teal-300 text-sm font-bold">
                                            <i class="fas fa-plus mr-1"></i> Koleksiyon Oluştur
                                        </a>
                                    </div>
                                @else
                                    <div class="p-2 max-h-60 overflow-y-auto">
                                        @foreach($collections as $col)
                                            <template x-if="isAdded({{ $col->id }})">
                                                {{-- Zaten ekliyse --}}
                                                <div class="flex items-center gap-3 p-3 rounded-xl bg-teal-900/20 border border-teal-500/20">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                                        style="background-color: {{ $col->color }}20; color: {{ $col->color }};">
                                                        <i class="fas fa-{{ $col->icon }}"></i>
                                                    </div>
                                                    <span class="text-teal-400 text-sm font-bold flex-1 truncate">{{ $col->name }}</span>
                                                    <i class="fas fa-check-circle text-teal-500 text-xs"></i>
                                                </div>
                                            </template>
                                            <template x-if="!isAdded({{ $col->id }})">
                                                {{-- Ekle butonu --}}
                                                <button type="button"
                                                    @click="addToCollection({{ $col->id }})"
                                                    :disabled="addingCollectionId === {{ $col->id }}"
                                                    class="w-full flex items-center gap-3 p-3 rounded-xl hover:bg-slate-800 transition-all text-left disabled:opacity-60 disabled:cursor-not-allowed">
                                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                                            style="background-color: {{ $col->color }}20; color: {{ $col->color }};">
                                                            <i class="fas fa-{{ $col->icon }}"></i>
                                                        </div>
                                                        <span class="text-slate-300 text-sm font-bold flex-1 truncate">{{ $col->name }}</span>
                                                        <i class="fas" :class="addingCollectionId === {{ $col->id }} ? 'fa-spinner fa-spin text-slate-500 text-xs' : 'fa-plus text-slate-600 text-xs'"></i>
                                                </button>
                                            </template>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div x-show="toast.show" x-transition x-cloak
                                class="absolute left-0 -bottom-14 z-[60] px-4 py-2 rounded-xl text-xs font-bold border"
                                :class="toast.type === 'success'
                                    ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40'
                                    : 'bg-red-500/15 text-red-300 border-red-500/40'">
                                <span x-text="toast.message"></span>
                            </div>
                        </div>

                        <form action="{{ route('movies.destroy', $movie) }}" method="POST"
                            onsubmit="return confirm('Bu filmi arşivinizden silmek istediğinize emin misiniz?')">
                            @csrf @method('DELETE')
                            <button class="bg-slate-800 hover:bg-red-600/20 hover:text-red-500 text-slate-400 px-6 py-3 rounded-2xl text-sm font-black transition-all border border-slate-700">
                                <i class="fas fa-trash-alt mr-2"></i> Sil
                            </button>
                        </form>

                        <a href="{{ url()->previous() }}"
                            class="bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white px-6 py-3 rounded-2xl text-sm font-black transition-all border border-slate-700 inline-flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Geri Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ALT BÖLÜM: Benzer Filmler --}}
        @if (count($similarMovies) > 0)
            <div class="mb-12">
                <h2 class="text-2xl font-black text-white mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-500/20 rounded-xl flex items-center justify-center text-indigo-400">
                        <i class="fas fa-film"></i>
                    </div>
                    Benzer Filmler
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                    @foreach ($similarMovies as $similar)
                        <div class="group relative bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 transition-all hover:scale-105 hover:shadow-xl hover:shadow-indigo-500/10">

                            {{-- Film ekle formu (tıklanınca izleme listesine ekler) --}}
                            <form action="{{ route('movies.store') }}" method="POST" class="absolute inset-0 z-30">
                                @csrf
                                <input type="hidden" name="tmdb_id" value="{{ $similar['id'] }}">
                                <input type="hidden" name="is_watched" value="0">
                                <button type="submit"
                                    class="w-full h-full bg-black/0 group-hover:bg-black/60 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer">
                                    <div class="bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-xl text-xs font-black shadow-lg transform scale-75 group-hover:scale-100 transition-all">
                                        <i class="fas fa-plus mr-1"></i> Listeye Ekle
                                    </div>
                                </button>
                            </form>

                            <div class="aspect-[2/3] bg-slate-800">
                                {{-- 📚 Benzer filmler için küçük resim (w342) - bandwidth tasarrufu --}}
                                <x-movie-poster 
                                    :path="$similar['poster_path'] ?? null" 
                                    :alt="$similar['title'] ?? 'Film'"
                                    size="w342"
                                />

                                @if ($similar['vote_average'] ?? null)
                                    <div class="absolute top-3 left-3 z-20">
                                        <div class="bg-black/70 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10">
                                            <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                            <span class="text-xs font-black">{{ number_format($similar['vote_average'], 1) }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="p-4">
                                <h4 class="text-white font-bold text-sm truncate" title="{{ $similar['title'] ?? '' }}">
                                    {{ $similar['title'] ?? 'Bilinmiyor' }}
                                </h4>
                                <span class="text-slate-500 text-xs">
                                    {{ isset($similar['release_date']) ? substr($similar['release_date'], 0, 4) : '-' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
@endsection
