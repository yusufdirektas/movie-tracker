@extends('layouts.app')

@section('title', 'Koleksiyonlarım')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════════════
         @KAVRAM: Alpine.js ile Tam Sayfa State Yönetimi
         - collections: Tüm koleksiyonlar array'i (reactive)
         - AJAX ile oluştur/sil işlemleri
         - Animasyonlu DOM güncellemeleri
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="container mx-auto"
         x-data="{
            collections: {{ json_encode($collections->map(function($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'color' => $c->color,
                    'icon' => $c->icon,
                    'movies_count' => $c->movies_count,
                    'url' => route('collections.show', $c),
                    'delete_url' => route('collections.destroy', $c),
                ];
            })->values()) }},
            modalOpen: false,
            form: {
                name: '',
                description: '',
                color: '#14b8a6',
                icon: 'folder',
                submitting: false,
                error: null
            },

            openModal() {
                this.modalOpen = true;
                this.form = { name: '', description: '', color: '#14b8a6', icon: 'folder', submitting: false, error: null };
                document.body.style.overflow = 'hidden';
                this.$nextTick(() => this.$refs.nameInput?.focus());
            },

            closeModal() {
                this.modalOpen = false;
                document.body.style.overflow = '';
            },

            async createCollection() {
                if (!this.form.name.trim()) {
                    this.form.error = 'Koleksiyon adı gerekli.';
                    return;
                }

                this.form.submitting = true;
                this.form.error = null;

                try {
                    const formData = new FormData();
                    formData.append('name', this.form.name);
                    formData.append('description', this.form.description);
                    formData.append('color', this.form.color);
                    formData.append('icon', this.form.icon);

                    const response = await fetch('{{ route('collections.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Yeni koleksiyonu listeye ekle
                        this.collections.push({
                            ...data.collection,
                            delete_url: `/collections/${data.collection.id}`
                        });
                        this.closeModal();
                    } else {
                        this.form.error = data.message || 'Bir hata oluştu.';
                    }
                } catch (error) {
                    console.error('Create error:', error);
                    this.form.error = 'Bir hata oluştu. Lütfen tekrar deneyin.';
                } finally {
                    this.form.submitting = false;
                }
            },

            async deleteCollection(index) {
                if (!confirm('Bu koleksiyonu silmek istediğine emin misin?')) return;

                const collection = this.collections[index];

                try {
                    const response = await fetch(collection.delete_url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        }
                    });

                    if (response.ok) {
                        // Card'ı animasyonla kaldır
                        this.collections.splice(index, 1);
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                    alert('Koleksiyon silinirken hata oluştu.');
                }
            }
         }">

        {{-- BAŞLIK --}}
        <div class="flex flex-col md:flex-row justify-between items-end mb-12">
            <h1 class="text-4xl font-extrabold text-white tracking-tight italic">
                Koleksiyon<span class="text-teal-400">larım</span>
            </h1>

            <button @click="openModal()"
                class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-teal-600/20 mt-4 md:mt-0">
                <i class="fas fa-plus"></i> Yeni Koleksiyon
            </button>
        </div>

        {{-- KOLEKSİYON KARTLARI --}}
        <template x-if="collections.length === 0">
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
                <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-layer-group text-4xl text-slate-600"></i>
                </div>
                <h3 class="text-white text-xl font-bold mb-2">Henüz koleksiyon oluşturmadın</h3>
                <p class="text-slate-500 mb-6">Filmlerini "Marvel Filmleri", "En İyiler" gibi özel koleksiyonlara ayırabilirsin.</p>
                <button @click="openModal()"
                    class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-3 rounded-xl font-bold transition-all">
                    <i class="fas fa-plus mr-2"></i> İlk Koleksiyonumu Oluştur
                </button>
            </div>
        </template>

        <template x-if="collections.length > 0">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="(collection, index) in collections" :key="collection.id">
                    <div class="group relative bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50 rounded-3xl p-6 hover:border-teal-500/50 transition-all duration-300 hover:shadow-xl hover:shadow-teal-500/5 hover:-translate-y-1">
                        <a :href="collection.url" class="block">
                            {{-- İkon ve Renk --}}
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-4 shadow-lg"
                                :style="`background-color: ${collection.color}20; color: ${collection.color};`">
                                <i :class="`fas fa-${collection.icon}`"></i>
                            </div>

                            {{-- İsim ve Açıklama --}}
                            <h3 class="text-white text-lg font-bold mb-1 group-hover:text-teal-400 transition-colors"
                                x-text="collection.name"></h3>
                            <p x-show="collection.description"
                               x-text="collection.description"
                               class="text-slate-500 text-sm mb-4 line-clamp-2"></p>

                            {{-- Film Sayısı --}}
                            <div class="flex items-center gap-2 text-slate-400 text-sm">
                                <i class="fas fa-film text-xs"></i>
                                <span class="font-bold" x-text="collection.movies_count"></span>
                                <span>film</span>
                            </div>
                        </a>

                        {{-- Sil Butonu (AJAX) --}}
                        <button @click.prevent.stop="deleteCollection(index)"
                            class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity w-8 h-8 bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>
        </template>

        {{-- YENİ KOLEKSİYON OLUŞTURMA MODALI (Alpine.js) --}}
        <div x-show="modalOpen"
             x-cloak
             @keydown.escape.window="closeModal()"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
             @click.self="closeModal()">
            <div class="bg-slate-900 border border-slate-700 rounded-3xl p-8 w-full max-w-md shadow-2xl"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <h2 class="text-white text-2xl font-bold mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 bg-teal-500/20 text-teal-400 rounded-xl flex items-center justify-center">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    Yeni Koleksiyon
                </h2>

                <form @submit.prevent="createCollection()" class="space-y-5">
                    <div>
                        <label class="text-slate-400 text-sm font-bold block mb-2">Koleksiyon Adı *</label>
                        <input type="text"
                               x-ref="nameInput"
                               x-model="form.name"
                               :disabled="form.submitting"
                               placeholder="Örn: Marvel Filmleri"
                               class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-600 disabled:opacity-50">
                    </div>

                    <div>
                        <label class="text-slate-400 text-sm font-bold block mb-2">Açıklama</label>
                        <input type="text"
                               x-model="form.description"
                               :disabled="form.submitting"
                               placeholder="Kısa bir açıklama (opsiyonel)"
                               class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-600 disabled:opacity-50">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-slate-400 text-sm font-bold block mb-2">Renk</label>
                            <input type="color"
                                   x-model="form.color"
                                   :disabled="form.submitting"
                                   class="w-full h-12 bg-slate-800 border border-slate-700 rounded-xl cursor-pointer disabled:opacity-50">
                        </div>
                        <div>
                            <label class="text-slate-400 text-sm font-bold block mb-2">İkon</label>
                            <select x-model="form.icon"
                                    :disabled="form.submitting"
                                    class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 disabled:opacity-50">
                                <option value="folder">📁 Klasör</option>
                                <option value="star">⭐ Yıldız</option>
                                <option value="heart">❤️ Kalp</option>
                                <option value="fire">🔥 Ateş</option>
                                <option value="crown">👑 Taç</option>
                                <option value="film">🎬 Film</option>
                                <option value="mask">🎭 Maske</option>
                                <option value="bolt">⚡ Şimşek</option>
                                <option value="ghost">👻 Hayalet</option>
                                <option value="trophy">🏆 Kupa</option>
                            </select>
                        </div>
                    </div>

                    {{-- Hata mesajı --}}
                    <p x-show="form.error" x-text="form.error" class="text-red-400 text-sm"></p>

                    <div class="flex gap-3 pt-2">
                        <button type="submit"
                                :disabled="form.submitting"
                                class="flex-1 bg-teal-600 hover:bg-teal-500 text-white py-3 rounded-xl font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!form.submitting">
                                <i class="fas fa-check mr-2"></i> Oluştur
                            </span>
                            <span x-show="form.submitting">
                                <i class="fas fa-spinner fa-spin mr-2"></i> Oluşturuluyor...
                            </span>
                        </button>
                        <button type="button"
                                @click="closeModal()"
                                :disabled="form.submitting"
                                class="px-6 bg-slate-800 text-slate-400 hover:text-white py-3 rounded-xl font-bold transition-all border border-slate-700 disabled:opacity-50">
                            İptal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
