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

            // Satır satır böl ve boşlukları temizle
            let lines = this.rawText.split('\n').filter(line => line.trim() !== '');
            let total = lines.length;

            for (let i = 0; i < total; i++) {
                let query = lines[i].trim();
                // Basit bir temizlik (Yıl bilgisini parantezden ayırabiliriz ama şimdilik direkt aratalım)

                try {
                    // Senin apiSearch rotanı kullanıyoruz
                    let res = await fetch('{{ route('movies.api_search') }}?query=' + encodeURIComponent(query));
                    let data = await res.json();

                    if(data && data.length > 0) {
                        // En iyi eşleşmeyi (ilk sıradakini) alıyoruz
                        let bestMatch = data[0];
                        this.candidates.push({
                            original: query,
                            found: true,
                            tmdb_id: bestMatch.id,
                            title: bestMatch.title,
                            year: bestMatch.release_date ? bestMatch.release_date.substring(0,4) : '-',
                            poster: bestMatch.poster_path,
                            status: 'pending' // pending, saved, error
                        });
                    } else {
                        this.candidates.push({ original: query, found: false, status: 'error' });
                    }
                } catch(e) {
                    console.error(e);
                }

                // İlerleme çubuğunu güncelle
                this.progress = Math.round(((i + 1) / total) * 100);
            }
            this.status = 'ready';
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
                    formData.append('is_watched', '1'); // Varsayılan olarak izlendi kabul ediyoruz (Not Defteri genelde izlenenlerdir)
                    formData.append('_token', '{{ csrf_token() }}'); // Güvenlik tokeni şart

                    let res = await fetch('{{ route('movies.store') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' }, // JSON istediğimizi belirtiyoruz
                        body: formData
                    });

                    if(res.ok) {
                        item.status = 'saved';
                    } else {
                        item.status = 'error';
                    }
                } catch(e) {
                    item.status = 'error';
                }

                // Hafif gecikme koyuyoruz ki sunucu boğulmasın
                await new Promise(r => setTimeout(r, 500));
            }
            this.status = 'done';
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
                <div class="bg-slate-900 border rounded-2xl p-3 flex gap-3 items-center transition-all"
                     :class="{
                        'border-slate-800': item.status === 'pending',
                        'border-emerald-500/50 bg-emerald-500/10': item.status === 'saved',
                        'border-red-500/50 opacity-70': item.status === 'error' || !item.found
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
                    </div>

                    <div class="flex-1 min-w-0">
                        <template x-if="item.found">
                            <div>
                                <h4 class="text-white text-sm font-bold truncate" x-text="item.title"></h4>
                                <p class="text-slate-500 text-xs" x-text="item.year"></p>
                                <p class="text-indigo-400 text-[10px] truncate" x-text="'Aranan: ' + item.original"></p>
                            </div>
                        </template>
                        <template x-if="!item.found">
                            <div>
                                <h4 class="text-red-400 text-sm font-bold">Bulunamadı</h4>
                                <p class="text-slate-500 text-xs truncate" x-text="item.original"></p>
                            </div>
                        </template>
                    </div>

                    <button x-show="item.status === 'pending'" @click="candidates.splice(index, 1)" class="text-slate-600 hover:text-red-400 p-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </template>
        </div>

        <div class="flex justify-end gap-4">
            <button @click="status = 'idle'; rawText = ''; candidates = []"
                    x-show="status === 'ready' || status === 'done'"
                    class="text-slate-400 hover:text-white px-6 py-3">
                İptal / Yeni Liste
            </button>

            <button @click="saveAll()"
                    x-show="status === 'ready' && candidates.filter(c => c.found).length > 0"
                    class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-emerald-600/20 flex items-center gap-2">
                <span>Hepsini Kaydet</span>
                <span class="bg-black/20 px-2 py-0.5 rounded text-sm" x-text="candidates.filter(c => c.found).length"></span>
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
