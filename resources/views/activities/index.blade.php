{{--
📚 AKTİVİTE FEED SAYFASI

Bu sayfa takip edilen kullanıcıların aktivitelerini gösterir.
Infinite scroll ile sayfa kaydırıldıkça daha fazla aktivite yüklenir.

@KAVRAM: Infinite Scroll Pattern
1. İlk yüklemede 20 aktivite göster
2. Sayfanın sonuna gelince AJAX ile daha fazla yükle
3. "before" parametresi ile cursor-based pagination

@KAVRAM: x-intersect (Alpine.js)
- Intersection Observer API'nin Alpine wrapper'ı
- Element viewport'a girince event tetikler
- Infinite scroll için ideal
--}}

@extends('layouts.app')

@section('title', 'Aktivite Feed')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="feedLoader()">

    {{-- Başlık --}}
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tighter">
            Aktivite <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Feed</span>
        </h1>
        <p class="text-slate-400 mt-2">
            Takip ettiğin kullanıcıların son aktiviteleri
        </p>
    </div>

    {{-- Aktivite Listesi --}}
    @if($activities->isEmpty())
        <div class="bg-slate-800/50 rounded-xl border border-slate-700/50 p-12 text-center">
            <div class="text-6xl mb-4">🎬</div>
            <h2 class="text-xl font-bold text-white mb-2">Henüz aktivite yok</h2>
            <p class="text-slate-400 mb-6">
                Diğer kullanıcıları takip etmeye başla, aktivitelerini burada gör!
            </p>
            <a href="{{ route('users.index') }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Kullanıcıları Keşfet
            </a>
        </div>
    @else
        {{-- Aktivite Grid --}}
        <div class="space-y-4" id="activity-list">
            @foreach($activities as $activity)
                <x-activity-card :activity="$activity" />
            @endforeach
        </div>

        {{-- Infinite Scroll Trigger --}}
        @if($activities->count() >= 20)
            <div
                x-show="hasMore && !loading"
                x-intersect:enter="loadMore()"
                class="flex justify-center py-8"
            >
                <div class="flex items-center gap-2 text-slate-400">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                    </svg>
                    <span>Yükleniyor...</span>
                </div>
            </div>
        @endif

        {{-- Loading State --}}
        <div x-show="loading" class="flex justify-center py-8">
            <div class="flex items-center gap-2 text-slate-400">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                <span>Daha fazla yükleniyor...</span>
            </div>
        </div>

        {{-- No More Content --}}
        <div x-show="!hasMore && !loading" class="text-center py-8">
            <p class="text-slate-500">Tüm aktiviteleri gördün 🎉</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
/**
 * 📚 FEED LOADER (Infinite Scroll)
 *
 * @KAVRAM: Alpine.js Component
 * - x-data="feedLoader()" ile bu fonksiyon çağrılır
 * - Dönen obje component'in state'i olur
 *
 * @KAVRAM: Cursor-Based Pagination
 * - lastId: Son yüklenen aktivitenin ID'si
 * - Server'a "bu ID'den öncekiler" diyoruz
 * - Yeni aktivite eklense bile liste tutarlı kalır
 */
function feedLoader() {
    return {
        loading: false,
        hasMore: {{ $activities->count() >= 20 ? 'true' : 'false' }},
        lastId: {{ $activities->last()?->id ?? 'null' }},

        /**
         * Daha fazla aktivite yükle
         *
         * @KAVRAM: fetch() API
         * - Modern JavaScript'te HTTP request yapmanın yolu
         * - Promise döner, async/await ile kullanılır
         * - headers ile JSON istediğimizi belirtiyoruz
         */
        async loadMore() {
            // Zaten yüklüyorsa veya daha fazla yoksa çık
            if (this.loading || !this.hasMore) return;

            this.loading = true;

            try {
                // AJAX request
                const response = await fetch(`{{ route('feed') }}?before=${this.lastId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                // Yeni aktiviteleri DOM'a ekle
                const container = document.getElementById('activity-list');
                data.activities.forEach(item => {
                    container.insertAdjacentHTML('beforeend', item.html);
                });

                // State güncelle
                this.hasMore = data.hasMore;
                this.lastId = data.lastId;

            } catch (error) {
                console.error('Feed yüklenirken hata:', error);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endpush
@endsection
