@extends('layouts.app')

@section('title', 'İçe Aktarma Geçmişi')

@section('content')
<div class="container mx-auto max-w-5xl" x-data="importHistory()">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">İçe Aktarma <span class="text-indigo-500">Geçmişi</span></h1>
            <p class="text-slate-400 mt-2">Tüm içe aktarma işlemlerini takip edin</p>
        </div>
        <a href="{{ route('movies.import') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-600/20">
            <i class="fas fa-plus mr-2"></i> Yeni İçe Aktarma
        </a>
    </div>

    @if($batches->isEmpty())
        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-12 text-center">
            <div class="text-6xl text-slate-700 mb-4"><i class="fas fa-inbox"></i></div>
            <h3 class="text-xl font-bold text-white mb-2">Henüz içe aktarma yok</h3>
            <p class="text-slate-400 mb-6">İlk toplu içe aktarmanızı başlatın</p>
            <a href="{{ route('movies.import') }}" class="inline-block bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-bold transition-all">
                İçe Aktarmaya Başla <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    @else
        <div class="space-y-4">
            @foreach($batches as $batch)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-slate-700 transition-colors"
                     x-data="{ expanded: false, batch: @js($batch->toArray()), items: [], loading: false, retrying: false, retryMessage: '', cancelling: false }"
                     :class="{ 'ring-2 ring-indigo-500/30': batch.status === 'processing' }">

                    {{-- Header --}}
                    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded; if(expanded && !items.length) loadItems()">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg"
                                 :class="{
                                     'bg-emerald-500/20 text-emerald-400': batch.status === 'finished',
                                     'bg-indigo-500/20 text-indigo-400': batch.status === 'processing',
                                     'bg-slate-700 text-slate-400': batch.status === 'queued'
                                 }">
                                <i :class="{
                                    'fas fa-check': batch.status === 'finished',
                                    'fas fa-spinner fa-spin': batch.status === 'processing',
                                    'fas fa-clock': batch.status === 'queued'
                                }"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-bold">
                                    Batch #{{ $batch->id }}
                                    <span class="text-xs ml-2 px-2 py-1 rounded-lg"
                                          :class="{
                                              'bg-emerald-500/20 text-emerald-300': batch.status === 'finished',
                                              'bg-indigo-500/20 text-indigo-300': batch.status === 'processing',
                                              'bg-slate-700 text-slate-300': batch.status === 'queued'
                                          }"
                                          x-text="batch.status === 'finished' ? 'Tamamlandı' : batch.status === 'processing' ? 'İşleniyor' : 'Bekliyor'"></span>
                                </h3>
                                <p class="text-slate-500 text-sm">
                                    {{ $batch->created_at->diffForHumans() }}
                                    · {{ $batch->is_watched ? 'İzlendi' : 'İzlenecek' }} olarak
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-6">
                            {{-- Stats --}}
                            <div class="hidden md:flex items-center gap-4 text-sm">
                                <span class="text-slate-400">
                                    <span class="font-bold text-white">{{ $batch->total_items }}</span> film
                                </span>
                                <span class="text-emerald-400">
                                    <i class="fas fa-check mr-1"></i>{{ $batch->success_items }}
                                </span>
                                <span class="text-amber-400">
                                    <i class="fas fa-copy mr-1"></i>{{ $batch->duplicate_items }}
                                </span>
                                <span class="text-red-400">
                                    <i class="fas fa-times mr-1"></i>{{ $batch->error_items + $batch->not_found_items }}
                                </span>
                            </div>

                            {{-- Progress --}}
                            @if($batch->status !== 'finished')
                                <div class="w-24 bg-slate-700 h-2 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full transition-all duration-300"
                                         style="width: {{ $batch->total_items > 0 ? round(($batch->processed_items / $batch->total_items) * 100) : 0 }}%"></div>
                                </div>
                                <button @click.stop="cancelBatch({{ $batch->id }})"
                                        :disabled="cancelling"
                                        class="text-xs bg-red-600/20 hover:bg-red-600/30 text-red-300 px-3 py-1 rounded-lg transition-colors disabled:opacity-50"
                                        title="İptal Et">
                                    <i class="fas" :class="cancelling ? 'fa-spinner fa-spin' : 'fa-stop'"></i>
                                </button>
                            @endif

                            <i class="fas fa-chevron-down text-slate-500 transition-transform" :class="{ 'rotate-180': expanded }"></i>
                        </div>
                    </div>

                    {{-- Expanded Details --}}
                    <div x-show="expanded" x-collapse style="display: none;">
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            {{-- Mobile Stats --}}
                            <div class="md:hidden grid grid-cols-4 gap-2 mb-4 text-xs text-center">
                                <div class="bg-slate-800 rounded-lg p-2">
                                    <div class="text-white font-bold">{{ $batch->total_items }}</div>
                                    <div class="text-slate-500">Toplam</div>
                                </div>
                                <div class="bg-slate-800 rounded-lg p-2">
                                    <div class="text-emerald-400 font-bold">{{ $batch->success_items }}</div>
                                    <div class="text-slate-500">Başarılı</div>
                                </div>
                                <div class="bg-slate-800 rounded-lg p-2">
                                    <div class="text-amber-400 font-bold">{{ $batch->duplicate_items }}</div>
                                    <div class="text-slate-500">Duplicate</div>
                                </div>
                                <div class="bg-slate-800 rounded-lg p-2">
                                    <div class="text-red-400 font-bold">{{ $batch->error_items + $batch->not_found_items }}</div>
                                    <div class="text-slate-500">Hata</div>
                                </div>
                            </div>

                            {{-- Items List --}}
                            <div x-show="loading" class="text-center py-8">
                                <i class="fas fa-spinner fa-spin text-2xl text-indigo-500"></i>
                            </div>

                            <div x-show="!loading && items.length" class="max-h-[40vh] overflow-y-auto space-y-2 pr-2">
                                <template x-for="item in items" :key="item.id">
                                    <div class="bg-slate-950 border rounded-xl p-3 text-xs flex items-center gap-3"
                                         :class="{
                                             'border-emerald-500/40': item.status === 'saved',
                                             'border-amber-500/40': item.status === 'duplicate',
                                             'border-red-500/40': item.status === 'error' || item.status === 'not_found',
                                             'border-indigo-500/40': item.status === 'processing',
                                             'border-slate-800': item.status === 'pending'
                                         }">
                                        <div class="text-slate-500 font-mono w-10" x-text="'#' + item.line_number"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-slate-200 truncate" x-text="item.original_query"></p>
                                            <p class="text-slate-500 truncate" x-show="item.resolved_title" x-text="item.resolved_title"></p>
                                            <p class="text-red-300 truncate" x-show="item.error_message" x-text="item.error_message"></p>
                                        </div>
                                        <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider"
                                              :class="{
                                                  'bg-emerald-500/20 text-emerald-300': item.status === 'saved',
                                                  'bg-amber-500/20 text-amber-300': item.status === 'duplicate',
                                                  'bg-red-500/20 text-red-300': item.status === 'error' || item.status === 'not_found',
                                                  'bg-indigo-500/20 text-indigo-300': item.status === 'processing',
                                                  'bg-slate-700 text-slate-300': item.status === 'pending'
                                              }"
                                              x-text="item.status"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Actions --}}
                            @if($batch->error_items > 0 || $batch->not_found_items > 0)
                                <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs text-slate-500">{{ $batch->error_items + $batch->not_found_items }} hatalı öğe</p>
                                        <p x-show="retryMessage" x-text="retryMessage" class="text-xs text-emerald-400 mt-1"></p>
                                    </div>
                                    <button @click="retryFailed({{ $batch->id }})"
                                            :disabled="retrying"
                                            class="text-sm bg-red-600/20 hover:bg-red-600/30 text-red-300 px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50">
                                        <i class="fas" :class="retrying ? 'fa-spinner fa-spin' : 'fa-redo'" class="mr-2"></i>
                                        <span x-text="retrying ? 'Yeniden Deneniyor...' : 'Hatalıları Yeniden Dene'"></span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $batches->links() }}
        </div>
    @endif
</div>

<script>
function importHistory() {
    return {
        async loadItems() {
            // This will be called from each batch's x-data context
        },

        async retryFailed(batchId) {
            this.retrying = true;
            this.retryMessage = '';

            try {
                const res = await fetch(`/movies/import-list/${batchId}/retry`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.retryMessage = data.message;
                    this.batch.status = 'processing';
                    // Reload items after short delay
                    setTimeout(() => this.loadItems(), 1000);
                } else {
                    this.retryMessage = data.message || 'Bir hata oluştu.';
                }
            } catch (e) {
                this.retryMessage = 'Ağ hatası oluştu.';
            }

            this.retrying = false;
        },

        async cancelBatch(batchId) {
            if (this.cancelling) return;
            this.cancelling = true;

            try {
                const res = await fetch(`/movies/import-list/${batchId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.batch.status = 'finished';
                    this.retryMessage = data.message;
                    // Reload page to refresh all data
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.retryMessage = data.message || 'İptal edilemedi.';
                }
            } catch (e) {
                this.retryMessage = 'Ağ hatası oluştu.';
            }

            this.cancelling = false;
        }
    };
}

// Per-batch item loading
document.addEventListener('alpine:init', () => {
    Alpine.data('batchRow', (batchId) => ({
        items: [],
        loading: false,
        async loadItems() {
            this.loading = true;
            try {
                const res = await fetch(`/movies/import-list/${batchId}/status`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    this.items = data.items;
                }
            } catch (_) {}
            this.loading = false;
        }
    }));
});
</script>
@endsection
