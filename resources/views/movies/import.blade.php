@extends('layouts.app')

@section('title', 'Toplu Film Ekle')

@section('content')
<div class="container mx-auto max-w-4xl"
     x-data="{
        rawText: '',
        status: 'idle', // idle, analyzing, ready, saving, done
        candidates: [],
        progress: 0,

        async analyzeList() {
            if(!this.rawText.trim()) return;
            this.status = 'analyzing';
            this.candidates = [];

            let lines = this.rawText.split('\n').filter(line => line.trim() !== '');
            let total = lines.length;

            for (let i = 0; i < total; i++) {
                let query = lines[i].trim();

                try {
                    let res = await fetch('{{ route('movies.api_search') }}?smart=1&query=' + encodeURIComponent(query));
                    let data = await res.json();

                    if(data.results && data.results.length > 0) {
                        let bestMatch = data.results[0];
                        this.candidates.push({
                            original: query,
                            found: true,
                            corrected: data.corrected || false,
                            corrected_query: data.corrected_query || null,
                            tmdb_id: bestMatch.id,
                            title: bestMatch.title,
                            year: bestMatch.release_date ? bestMatch.release_date.substring(0,4) : '-',
                            poster: bestMatch.poster_path,
                            suggestions: [],
                            status: 'pending'
                        });
                    } else if(data.suggestions && data.suggestions.length > 0) {
                        // Kesin eşleşme yok ama öneriler var → kullanıcıya sor
                        this.candidates.push({
                            original: query,
                            found: false,
                            suggestions: data.suggestions.map(s => ({
                                tmdb_id: s.id,
                                title: s.title,
                                year: s.release_date ? s.release_date.substring(0,4) : '-',
                                poster: s.poster_path,
                            })),
                            status: 'needs_selection'
                        });
                    } else {
                        this.candidates.push({ original: query, found: false, suggestions: [], status: 'not_found' });
                    }
                } catch(e) {
                    console.error(e);
                }

                this.progress = Math.round(((i + 1) / total) * 100);
            }
            this.status = 'ready';
        },

        pickSuggestion(candidateIndex, suggestion) {
            let item = this.candidates[candidateIndex];
            item.found = true;
            item.tmdb_id = suggestion.tmdb_id;
            item.title = suggestion.title;
            item.year = suggestion.year;
            item.poster = suggestion.poster;
            item.corrected = true;
            item.corrected_query = suggestion.title;
            item.suggestions = [];
            item.status = 'pending';
        },

        skipCandidate(candidateIndex) {
            this.candidates[candidateIndex].status = 'skipped';
        },

        async saveAll() {
            this.status = 'saving';
            let itemsToSave = this.candidates.filter(c => c.found && c.status === 'pending');
            let total = itemsToSave.length;

            for (let i = 0; i < total; i++) {
                let item = itemsToSave[i];

                try {
                    let formData = new FormData();
                    formData.append('tmdb_id', item.tmdb_id);
                    formData.append('is_watched', '1');
                    formData.append('_token', '{{ csrf_token() }}');

                    let res = await fetch('{{ route('movies.store') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: formData
                    });

                    let data = await res.json();

                    if (data.success) {
                        item.status = 'saved';
                    } else if (res.status === 400) {
                        item.status = 'duplicate';
                    } else {
                        item.status = 'error';
                    }
                } catch(e) {
                    item.status = 'error';
                }

                await new Promise(r => setTimeout(r, 500));
            }
            this.status = 'done';
        },

        get needsAttention() {
            return this.candidates.filter(c => c.status === 'needs_selection').length;
        }
     }">

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-white tracking-tight">Toplu <span class="text-indigo-500">İçe Aktar</span></h1>
            <p class="text-slate-400 mt-2">Listenizi yapıştırın, gerisini bize bırakın.</p>
        </div>
        <a href="{{ route('movies.index') }}" class="text-slate-500 hover:text-white transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Geri Dön
        </a>
    </div>

    <div x-show="status === 'idle'" class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl">
        <label class="block text-white font-bold mb-4">Film Listesi (Her satıra bir film)</label>
        <textarea x-model="rawText"
                  placeholder="Örnek:&#10;The Matrix&#10;Inception&#10;Interstellar&#10;..."
                  class="w-full h-64 bg-slate-950 text-slate-300 border border-slate-800 rounded-xl p-4 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono text-sm leading-relaxed"
        ></textarea>

        <div class="mt-6 flex justify-between items-center">
            <p class="text-xs text-slate-500 italic">Not: Letterboxd CSV dosyasındaki 'Name' sütununu kopyalayıp buraya yapıştırabilirsin.</p>
            <button @click="analyzeList()"
                    :disabled="!rawText.trim()"
                    class="bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed text-white px-8 py-3 rounded-xl font-bold transition-all shadow-lg shadow-indigo-600/20">
                Listeyi Analiz Et <i class="fas fa-magic ml-2"></i>
            </button>
        </div>
    </div>

    <div x-show="status !== 'idle'" style="display: none;">

        <div class="mb-6 bg-slate-800 rounded-2xl p-4 flex items-center gap-4">
            <div class="flex-1 bg-slate-700 h-3 rounded-full overflow-hidden">
                <div class="bg-indigo-500 h-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
            </div>
            <span class="text-white font-mono font-bold" x-text="progress + '%'"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
            <template x-for="(item, index) in candidates" :key="index">
                <div>
                    {{-- Normal kart (bulunan, kaydedilen, hatalı) --}}
                    <template x-if="item.status !== 'needs_selection'">
                        <div class="bg-slate-900 border rounded-2xl p-3 flex gap-3 items-center transition-all"
                             :class="{
                                'border-slate-800': item.status === 'pending',
                                'border-emerald-500/50 bg-emerald-500/10': item.status === 'saved',
                                'border-amber-500/50 bg-amber-500/10': item.status === 'duplicate',
                                'border-red-500/50 opacity-70': item.status === 'not_found',
                                'border-slate-700 opacity-50': item.status === 'skipped',
                                'border-red-500/50 opacity-70': item.status === 'error'
                             }">

                            <div class="w-12 h-16 bg-slate-800 rounded-lg flex-shrink-0 overflow-hidden relative">
                                <template x-if="item.found && item.poster">
                                    <img :src="'https://image.tmdb.org/t/p/w92' + item.poster" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!item.found">
                                    <div class="flex items-center justify-center h-full text-slate-600"><i class="fas fa-question"></i></div>
                                </template>

                                <div x-show="item.status === 'saved'" class="absolute inset-0 bg-emerald-900/80 flex items-center justify-center">
                                    <i class="fas fa-check text-white"></i>
                                </div>
                                <div x-show="item.status === 'duplicate'" class="absolute inset-0 bg-amber-900/80 flex items-center justify-center">
                                    <i class="fas fa-bookmark text-white"></i>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <template x-if="item.found">
                                    <div>
                                        <h4 class="text-white text-sm font-bold truncate" x-text="item.title"></h4>
                                        <p class="text-slate-500 text-xs" x-text="item.year"></p>
                                        <p x-show="item.status === 'duplicate'" class="text-amber-400 text-[10px] font-bold uppercase tracking-wider">Zaten arşivinde var</p>
                                        <p x-show="item.corrected && item.status !== 'duplicate'" class="text-teal-400 text-[10px] truncate">
                                            <i class="fas fa-spell-check mr-1"></i> <span x-text="item.original"></span> → düzeltildi
                                        </p>
                                        <p x-show="!item.corrected && item.status !== 'duplicate'" class="text-indigo-400 text-[10px] truncate" x-text="'Aranan: ' + item.original"></p>
                                    </div>
                                </template>
                                <template x-if="item.status === 'not_found'">
                                    <div>
                                        <h4 class="text-red-400 text-sm font-bold">Bulunamadı</h4>
                                        <p class="text-slate-500 text-xs truncate" x-text="item.original"></p>
                                    </div>
                                </template>
                                <template x-if="item.status === 'skipped'">
                                    <div>
                                        <h4 class="text-slate-500 text-sm font-bold line-through">Atlandı</h4>
                                        <p class="text-slate-600 text-xs truncate" x-text="item.original"></p>
                                    </div>
                                </template>
                            </div>

                            <button x-show="item.status === 'pending'" @click="candidates.splice(index, 1)" class="text-slate-600 hover:text-red-400 p-2">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </template>

                    {{-- Öneri kartı: "Bunu mu demek istediniz?" --}}
                    <template x-if="item.status === 'needs_selection'">
                        <div class="bg-slate-900 border-2 border-violet-500/50 rounded-2xl p-4 transition-all">
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fas fa-lightbulb text-violet-400"></i>
                                <span class="text-violet-300 text-xs font-bold uppercase tracking-wider">Bunu mu demek istediniz?</span>
                            </div>
                            <p class="text-slate-400 text-xs mb-3 truncate">
                                Aranan: <span class="text-white font-medium" x-text="item.original"></span>
                            </p>

                            <div class="space-y-2">
                                <template x-for="(sug, sIndex) in item.suggestions" :key="sIndex">
                                    <button @click="pickSuggestion(index, sug)"
                                            class="w-full flex items-center gap-3 bg-slate-800 hover:bg-violet-900/40 border border-slate-700 hover:border-violet-500 rounded-xl p-2 transition-all text-left group">
                                        <div class="w-10 h-14 bg-slate-700 rounded-lg flex-shrink-0 overflow-hidden">
                                            <template x-if="sug.poster">
                                                <img :src="'https://image.tmdb.org/t/p/w92' + sug.poster" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!sug.poster">
                                                <div class="flex items-center justify-center h-full text-slate-600"><i class="fas fa-film"></i></div>
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h5 class="text-white text-sm font-bold truncate group-hover:text-violet-300" x-text="sug.title"></h5>
                                            <p class="text-slate-500 text-xs" x-text="sug.year"></p>
                                        </div>
                                        <i class="fas fa-plus-circle text-slate-600 group-hover:text-violet-400 text-lg"></i>
                                    </button>
                                </template>
                            </div>

                            <button @click="skipCandidate(index)"
                                    class="mt-3 w-full text-center text-slate-600 hover:text-red-400 text-xs py-1 transition-colors">
                                <i class="fas fa-forward mr-1"></i> Hiçbiri değil, atla
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <div x-show="needsAttention > 0 && status === 'ready'" class="mb-4 bg-violet-900/30 border border-violet-500/40 rounded-2xl p-4 flex items-center gap-3">
            <i class="fas fa-hand-point-up text-violet-400 text-lg"></i>
            <p class="text-violet-200 text-sm">
                <span class="font-bold" x-text="needsAttention"></span> film için seçim yapmanız gerekiyor.
                Yukarıdaki mor kartlardan birini seçin veya atlayın.
            </p>
        </div>

        <div class="flex justify-end gap-4">
            <button @click="status = 'idle'; rawText = ''; candidates = []"
                    x-show="status === 'ready' || status === 'done'"
                    class="text-slate-400 hover:text-white px-6 py-3">
                İptal / Yeni Liste
            </button>

            <button @click="saveAll()"
                    x-show="status === 'ready' && candidates.filter(c => c.found && c.status === 'pending').length > 0 && needsAttention === 0"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-emerald-600/20 flex items-center gap-2">
                <span>Hepsini Kaydet</span>
                <span class="bg-black/20 px-2 py-0.5 rounded text-sm" x-text="candidates.filter(c => c.found && c.status === 'pending').length"></span>
            </button>

            <a href="{{ route('movies.index') }}"
               x-show="status === 'done'"
               class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-indigo-600/20">
                Arşive Git
            </a>
        </div>
    </div>
</div>
@endsection
