{{--
    📚 MOVIE POSTER COMPONENT
    
    Gelişmiş lazy loading özellikleri:
    1. Native lazy loading (loading="lazy")
    2. Skeleton loading animasyonu
    3. Hata durumunda fallback ikonu
    4. Fade-in geçiş animasyonu
    
    Kullanım:
    <x-movie-poster :path="$movie->poster_path" :alt="$movie->title" />
    <x-movie-poster :path="$movie->poster_path" size="w342" class="rounded-lg" />
--}}

@if ($imageUrl())
<div
    x-data="{
        baseUrl: @js($imageUrl()),
        imageSrc: @js($imageUrl()),
        loaded: false,
        error: false,
        retryCount: 0,
        init() {
            // Alpine bind edilmeden önce görsel yüklenmiş olabilir.
            if (this.$refs.poster?.complete) {
                if (this.$refs.poster.naturalWidth > 0) {
                    this.loaded = true;
                } else {
                    this.error = true;
                }
            }
        },
        retry() {
            this.retryCount++;
            this.error = false;
            this.loaded = false;
            const separator = this.baseUrl.includes('?') ? '&' : '?';
            this.imageSrc = `${this.baseUrl}${separator}retry=${this.retryCount}`;
        }
    }"
    class="relative w-full h-full overflow-hidden {{ $class }}"
>
    {{-- Skeleton Loading --}}
    <div x-show="!loaded && !error" class="absolute inset-0 bg-slate-800 animate-pulse flex items-center justify-center">
        <svg class="w-12 h-12 text-slate-700 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    {{-- Hata Durumu --}}
    <div x-show="error" x-cloak class="absolute inset-0 bg-slate-900/95 flex items-center justify-center p-4">
        <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-slate-800/70 border border-slate-700 flex items-center justify-center">
                <i class="fas fa-image text-2xl text-slate-600"></i>
            </div>
            <p class="text-slate-400 text-xs font-bold mb-3">Poster yüklenemedi</p>
            <button type="button"
                @click="retry()"
                class="text-[11px] font-bold px-3 py-1.5 rounded-lg bg-indigo-500/20 text-indigo-300 hover:bg-indigo-500/30 border border-indigo-500/30 transition">
                <i class="fas fa-rotate-right mr-1"></i> Tekrar Dene
            </button>
        </div>
    </div>

    {{-- Gerçek Resim --}}
    <img
        x-ref="poster"
        x-bind:src="imageSrc"
        alt="{{ $alt }}"
        loading="lazy"
        decoding="async"
        x-on:load="loaded = true"
        x-on:error="error = true"
        x-bind:class="loaded ? 'opacity-100 scale-100' : 'opacity-0 scale-105'"
        class="w-full h-full object-cover transition-all duration-500 ease-out"
    />
</div>
@else
<div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950 {{ $class }}">
    <i class="fas fa-image text-4xl"></i>
</div>
@endif
