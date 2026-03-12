@extends('layouts.app')

@section('title', 'Koleksiyonlarım')

@section('content')
    <div class="container mx-auto">

        {{-- BAŞLIK --}}
        <div class="flex flex-col md:flex-row justify-between items-end mb-12">
            <h1 class="text-4xl font-extrabold text-white tracking-tight italic">
                Koleksiyon<span class="text-teal-400">larım</span>
            </h1>

            <button onclick="openCreateModal()"
                class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 transition-all shadow-lg shadow-teal-600/20 mt-4 md:mt-0">
                <i class="fas fa-plus"></i> Yeni Koleksiyon
            </button>
        </div>

        {{-- KOLEKSİYON KARTLARI --}}
        @if($collections->isEmpty())
            <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
                <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <i class="fas fa-layer-group text-4xl text-slate-600"></i>
                </div>
                <h3 class="text-white text-xl font-bold mb-2">Henüz koleksiyon oluşturmadın</h3>
                <p class="text-slate-500 mb-6">Filmlerini "Marvel Filmleri", "En İyiler" gibi özel koleksiyonlara ayırabilirsin.</p>
                <button onclick="openCreateModal()"
                    class="bg-teal-600 hover:bg-teal-500 text-white px-6 py-3 rounded-xl font-bold transition-all">
                    <i class="fas fa-plus mr-2"></i> İlk Koleksiyonumu Oluştur
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($collections as $collection)
                    <a href="{{ route('collections.show', $collection) }}"
                        class="group relative bg-gradient-to-br from-slate-900 to-slate-800 border border-slate-700/50 rounded-3xl p-6 hover:border-teal-500/50 transition-all duration-300 hover:shadow-xl hover:shadow-teal-500/5 hover:-translate-y-1">

                        {{-- İkon ve Renk --}}
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl mb-4 shadow-lg"
                            style="background-color: {{ $collection->color }}20; color: {{ $collection->color }};">
                            <i class="fas fa-{{ $collection->icon }}"></i>
                        </div>

                        {{-- İsim ve Açıklama --}}
                        <h3 class="text-white text-lg font-bold mb-1 group-hover:text-teal-400 transition-colors">
                            {{ $collection->name }}
                        </h3>
                        @if($collection->description)
                            <p class="text-slate-500 text-sm mb-4 line-clamp-2">{{ $collection->description }}</p>
                        @endif

                        {{-- Film Sayısı --}}
                        <div class="flex items-center gap-2 text-slate-400 text-sm">
                            <i class="fas fa-film text-xs"></i>
                            <span class="font-bold">{{ $collection->movies_count }}</span>
                            <span>film</span>
                        </div>

                        {{-- Sil Butonu --}}
                        <form action="{{ route('collections.destroy', $collection) }}" method="POST"
                            class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity"
                            onsubmit="return confirm('Bu koleksiyonu silmek istediğine emin misin?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-8 h-8 bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white rounded-lg flex items-center justify-center transition-all text-xs">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- YENİ KOLEKSİYON OLUŞTURMA MODALI --}}
    <div id="createModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" style="display: none;"
        onclick="if(event.target === this) closeCreateModal()">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl p-8 w-full max-w-md shadow-2xl">
            <h2 class="text-white text-2xl font-bold mb-6 flex items-center gap-3">
                <div class="w-10 h-10 bg-teal-500/20 text-teal-400 rounded-xl flex items-center justify-center">
                    <i class="fas fa-layer-group"></i>
                </div>
                Yeni Koleksiyon
            </h2>

            <form action="{{ route('collections.store') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="text-slate-400 text-sm font-bold block mb-2">Koleksiyon Adı *</label>
                    <input type="text" name="name" required placeholder="Örn: Marvel Filmleri"
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-600">
                </div>

                <div>
                    <label class="text-slate-400 text-sm font-bold block mb-2">Açıklama</label>
                    <input type="text" name="description" placeholder="Kısa bir açıklama (opsiyonel)"
                        class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500 placeholder-slate-600">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-slate-400 text-sm font-bold block mb-2">Renk</label>
                        <input type="color" name="color" value="#14b8a6"
                            class="w-full h-12 bg-slate-800 border border-slate-700 rounded-xl cursor-pointer">
                    </div>
                    <div>
                        <label class="text-slate-400 text-sm font-bold block mb-2">İkon</label>
                        <select name="icon"
                            class="w-full bg-slate-800 border border-slate-700 text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
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

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                        class="flex-1 bg-teal-600 hover:bg-teal-500 text-white py-3 rounded-xl font-bold transition-all">
                        <i class="fas fa-check mr-2"></i> Oluştur
                    </button>
                    <button type="button" onclick="closeCreateModal()"
                        class="px-6 bg-slate-800 text-slate-400 hover:text-white py-3 rounded-xl font-bold transition-all border border-slate-700">
                        İptal
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function openCreateModal() {
        const modal = document.getElementById('createModal');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeCreateModal() {
        const modal = document.getElementById('createModal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
</script>
@endpush
