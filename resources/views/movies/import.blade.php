@extends('layouts.app')

@section('title', 'Toplu Film Ekle')

@section('content')
<div class="container mx-auto max-w-4xl"
     x-data="importRunner()">

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Asenkron <span class="text-indigo-500">Toplu İçe Aktar</span></h1>
            <p class="text-slate-400 mt-2">Listeyi gönder, kuyruğa alalım; sonuçları canlı takip et.</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('movies.import.history') }}" class="text-indigo-400 hover:text-indigo-300 transition-colors text-sm">
                <i class="fas fa-history mr-1"></i> Geçmiş
            </a>
            <a href="{{ route('movies.index') }}" class="text-slate-500 hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Geri Dön
            </a>
        </div>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl mb-6">
        <label class="block text-white font-bold mb-4">İçerik Listesi (her satıra bir film/dizi)</label>
        <textarea x-model="rawText"
                  placeholder="Örnek:&#10;The Matrix&#10;Inception&#10;Interstellar&#10;Breaking Bad&#10;..."
                  class="w-full h-64 bg-slate-950 text-slate-300 border border-slate-800 rounded-xl p-4 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm leading-relaxed"
        ></textarea>

        <div class="mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" x-model="isWatched" class="rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500/40">
                    İzlendi olarak ekle
                </label>
                <p class="text-xs text-slate-500 italic mt-2">Kuyruk worker çalıştır: <code class="text-slate-300">php artisan queue:work --queue=imports,default</code></p>
            </div>
            <button @click="startImport()"
                    :disabled="running || !rawText.trim()"
                    class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-600/20">
                İçe Aktarmayı Başlat <i class="fas fa-rocket ml-2"></i>
            </button>
        </div>
    </div>

    <div x-show="batch" style="display:none;" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white">Batch #<span x-text="batch?.id"></span></h2>
            <span class="text-xs px-2 py-1 rounded-lg font-bold"
                  :class="batchBadgeClass(batch?.status)"
                  x-text="batch?.status"></span>
        </div>

        <div class="mb-6 bg-slate-800 rounded-2xl p-4 flex items-center gap-4">
            <div class="flex-1 bg-slate-700 h-3 rounded-full overflow-hidden">
                <div class="bg-indigo-500 h-full transition-all duration-300" :style="'width: ' + progressPercent() + '%'"></div>
            </div>
            <span class="text-white font-mono font-bold" x-text="progressPercent() + '%'"></span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6 text-xs">
            <div class="bg-slate-800 rounded-xl p-3 text-slate-300">Toplam: <span class="font-bold text-white" x-text="batch?.total_items || 0"></span></div>
            <div class="bg-slate-800 rounded-xl p-3 text-slate-300">Başarılı: <span class="font-bold text-emerald-300" x-text="batch?.success_items || 0"></span></div>
            <div class="bg-slate-800 rounded-xl p-3 text-slate-300">Duplicate: <span class="font-bold text-amber-300" x-text="batch?.duplicate_items || 0"></span></div>
            <div class="bg-slate-800 rounded-xl p-3 text-slate-300">Hata: <span class="font-bold text-red-300" x-text="(batch?.error_items || 0) + (batch?.not_found_items || 0)"></span></div>
        </div>

        <div class="max-h-[50vh] overflow-y-auto space-y-2 pr-2">
            <template x-for="item in items" :key="item.id">
                <div class="bg-slate-950 border rounded-xl p-3 text-xs flex items-center gap-3"
                     :class="itemRowClass(item.status)">
                    <div class="text-slate-500 font-mono w-10" x-text="'#' + item.line_number"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-slate-200 truncate" x-text="item.original_query"></p>
                        <p class="text-slate-500 truncate" x-show="item.resolved_title" x-text="item.resolved_title"></p>
                        <p class="text-red-300 truncate" x-show="item.error_message" x-text="item.error_message"></p>
                    </div>
                    <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider"
                          :class="itemBadgeClass(item.status)"
                          x-text="item.status"></span>
                </div>
            </template>
        </div>

        <div class="mt-4 flex items-center justify-between">
            <p class="text-xs text-slate-500" x-text="statusMessage"></p>
            <a href="{{ route('movies.index') }}"
               class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-2 rounded-xl text-sm font-bold shadow-lg shadow-indigo-600/20">
                Arşive Git
            </a>
        </div>
    </div>
</div>

<script>
function importRunner() {
    return {
        rawText: '',
        isWatched: true,
        running: false,
        batch: null,
        items: [],
        pollTimer: null,
        statusMessage: '',

        async startImport() {
            if (!this.rawText.trim() || this.running) return;
            this.running = true;
            this.statusMessage = 'Batch oluşturuluyor...';

            try {
                const res = await fetch('{{ route('movies.import.start') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        raw_text: this.rawText,
                        is_watched: this.isWatched ? 1 : 0,
                    }),
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    this.statusMessage = data.message || 'Import başlatılamadı.';
                    this.running = false;
                    return;
                }

                this.batch = { id: data.batch_id, status: 'queued', total_items: 0, processed_items: 0 };
                this.items = [];
                this.pollStatus();
                this.pollTimer = setInterval(() => this.pollStatus(), 1500);
            } catch (e) {
                this.statusMessage = 'Ağ hatası oluştu.';
            }
        },

        async pollStatus() {
            if (!this.batch?.id) return;

            try {
                const res = await fetch(`{{ url('/movies/import-list') }}/${this.batch.id}/status`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const data = await res.json();
                this.batch = data.batch;
                this.items = data.items;
                this.statusMessage = `İşlenen: ${this.batch.processed_items}/${this.batch.total_items}`;

                if (this.batch.status === 'finished') {
                    this.running = false;
                    this.stopPolling();
                    this.statusMessage = `Tamamlandı. Başarılı: ${this.batch.success_items}, Duplicate: ${this.batch.duplicate_items}, Hata: ${this.batch.error_items + this.batch.not_found_items}`;
                }
            } catch (_) {
            }
        },

        stopPolling() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },

        progressPercent() {
            if (!this.batch || !this.batch.total_items) return 0;
            return Math.round((this.batch.processed_items / this.batch.total_items) * 100);
        },

        batchBadgeClass(status) {
            if (status === 'finished') return 'bg-emerald-500/20 text-emerald-300';
            if (status === 'processing') return 'bg-indigo-500/20 text-indigo-300';
            return 'bg-slate-700 text-slate-300';
        },

        itemBadgeClass(status) {
            if (status === 'saved') return 'bg-emerald-500/20 text-emerald-300';
            if (status === 'duplicate') return 'bg-amber-500/20 text-amber-300';
            if (status === 'error' || status === 'not_found') return 'bg-red-500/20 text-red-300';
            if (status === 'processing') return 'bg-indigo-500/20 text-indigo-300';
            return 'bg-slate-700 text-slate-300';
        },

        itemRowClass(status) {
            if (status === 'saved') return 'border-emerald-500/40';
            if (status === 'duplicate') return 'border-amber-500/40';
            if (status === 'error' || status === 'not_found') return 'border-red-500/40';
            if (status === 'processing') return 'border-indigo-500/40';
            return 'border-slate-800';
        },
    };
}
</script>
@endsection
