@extends('layouts.app')

@section('title', 'Profil Ayarları')

@section('header')
    <h2 class="font-semibold text-xl text-white leading-tight">
        {{ __('Profil Ayarları') }}
    </h2>
@endsection

@section('content')
<div class="space-y-6 max-w-4xl mx-auto" x-data="avatarUploader()">

    {{-- 📚 AVATAR ÖNİZLEME MODAL --}}
    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80"
         x-cloak>
        
        <div class="bg-slate-900 rounded-2xl shadow-2xl max-w-xl w-full max-h-[90vh] overflow-hidden border border-slate-700"
             @click.outside="closePreview()">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-4 border-b border-slate-700">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-eye text-indigo-400"></i>
                    Fotoğraf Önizleme
                </h3>
                <button @click="closePreview()" class="text-slate-400 hover:text-white transition">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-4">
                <div class="bg-slate-950 rounded-xl p-6 flex items-center justify-center min-h-[320px]">
                    <img :src="previewDataUrl" alt="Avatar önizleme"
                         class="w-48 h-48 rounded-full object-cover border-4 border-slate-700 shadow-2xl">
                </div>
            </div>
            
            {{-- Modal Footer --}}
            <div class="flex items-center justify-end gap-3 p-4 border-t border-slate-700 bg-slate-800/50">
                <button type="button" @click="closePreview()" 
                        class="px-4 py-2 text-slate-400 hover:text-white transition">
                    İptal
                </button>
                <button type="button" @click="saveAvatar()"
                        :disabled="saving"
                        class="px-6 py-2 bg-indigo-500 hover:bg-indigo-600 disabled:bg-indigo-500/50 text-white rounded-xl font-medium transition flex items-center gap-2">
                    <i class="fas fa-check" x-show="!saving"></i>
                    <i class="fas fa-spinner fa-spin" x-show="saving"></i>
                    <span x-text="saving ? 'Kaydediliyor...' : 'Onayla ve Kaydet'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- 📚 AVATAR VE HAKKINDA BÖLÜMÜ --}}
    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-id-card text-pink-400"></i> Profil Kartı
        </h3>

        <div class="flex flex-col md:flex-row gap-8">
            {{-- Avatar Bölümü --}}
            <div class="flex flex-col items-center">
                <div class="relative group">
                    <img src="{{ $user->avatar_url }}"
                         alt="{{ $user->name }}"
                         id="current-avatar"
                         class="w-32 h-32 rounded-full object-cover border-4 border-slate-700 shadow-xl">

                    {{-- Avatar Değiştir Overlay --}}
                    <label for="avatar-input"
                           class="absolute inset-0 bg-black/60 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                        <i class="fas fa-camera text-white text-2xl"></i>
                    </label>
                </div>

                {{-- Gizli File Input --}}
                <input type="file"
                       id="avatar-input"
                       accept="image/*"
                       class="hidden"
                       @change="openPreview($event)">
                
                <p class="text-slate-500 text-xs mt-3">Tıkla ve fotoğraf seç</p>

                <div x-show="notice.show" x-transition x-cloak class="mt-3 w-full max-w-xs text-xs rounded-xl border px-3 py-2"
                     :class="notice.type === 'error'
                        ? 'bg-red-500/10 border-red-500/40 text-red-300'
                        : 'bg-emerald-500/10 border-emerald-500/40 text-emerald-300'">
                    <div class="flex items-start gap-2">
                        <i class="fas mt-0.5" :class="notice.type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
                        <span class="flex-1" x-text="notice.message"></span>
                        <button type="button" class="opacity-70 hover:opacity-100" @click="notice.show = false" aria-label="Avatar mesajını kapat">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                @if($user->avatar)
                    <form id="delete-avatar-form" action="{{ route('profile.avatar.delete') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-400 hover:text-red-300 mt-2">
                            <i class="fas fa-trash mr-1"></i> Avatarı Kaldır
                        </button>
                    </form>
                @endif

                @error('avatar')
                    <p class="text-red-400 text-xs mt-2">{{ $message }}</p>
                @enderror

                @if(session('status') === 'avatar-updated')
                    <p class="text-green-400 text-xs mt-2">Avatar güncellendi!</p>
                @endif
            </div>

            {{-- Bio ve Gizlilik --}}
            <div class="flex-1">
                <form action="{{ route('profile.bio.update') }}" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="mb-4">
                        <label for="bio" class="block text-sm font-medium text-slate-400 mb-2">Hakkında</label>
                        <textarea id="bio"
                                  name="bio"
                                  rows="4"
                                  maxlength="500"
                                  placeholder="Kendinden bahset... Film zevklerin, favori türlerin..."
                                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition">{{ old('bio', $user->bio) }}</textarea>
                        <p class="text-xs text-slate-500 mt-1">
                            <span x-data="{ count: {{ strlen($user->bio ?? '') }} }" x-text="count"></span>/500 karakter
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="is_public"
                                   value="1"
                                   {{ $user->is_public ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500/20">
                            <span class="text-slate-300">
                                <i class="fas fa-globe text-green-400 mr-1"></i>
                                Profilim herkese açık olsun
                            </span>
                        </label>
                        <p class="text-xs text-slate-500 mt-1 ml-8">Kapalıysa sadece takip edenler profilinizi görebilir</p>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   name="show_recent_activities"
                                   value="1"
                                   {{ $user->show_recent_activities ? 'checked' : '' }}
                                   class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-indigo-500 focus:ring-indigo-500/20">
                            <span class="text-slate-300">
                                <i class="fas fa-clock-rotate-left text-cyan-400 mr-1"></i>
                                Son aktiviteler kartı profilimde görünsün
                            </span>
                        </label>
                        <p class="text-xs text-slate-500 mt-1 ml-8">Kapalıysa son aktiviteler sadece senin profil düzenleme ekranında görünür.</p>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
                        <i class="fas fa-save mr-1"></i> Kaydet
                    </button>

                    @if(session('status') === 'bio-updated')
                        <span class="text-green-400 text-sm ml-3">Kaydedildi!</span>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- 📚 VİTRİN FİLMLERİ BÖLÜMÜ --}}
    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
            <i class="fas fa-star text-yellow-400"></i> Vitrin Filmleri
        </h3>
        <p class="text-sm text-slate-400 mb-6">Profilinde öne çıkarmak istediğin en fazla 5 film seç</p>

        @if($availableMovies->isEmpty())
            <div class="text-center py-8 text-slate-500">
                <i class="fas fa-film text-4xl mb-3"></i>
                <p>Henüz izlediğin film yok.</p>
                <a href="{{ route('movies.create') }}" class="text-indigo-400 hover:text-indigo-300 text-sm">
                    Film ekle →
                </a>
            </div>
        @else
            <form action="{{ route('profile.showcase.update') }}" method="POST" x-data="showcaseSelector()">
                @csrf
                @method('PATCH')

                {{-- Seçilen Filmler --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-400 mb-3">Seçilen Filmler (<span x-text="selected.length"></span>/5)</label>

                    <div class="flex flex-wrap gap-3 min-h-[100px] p-4 bg-slate-950 rounded-xl border-2 border-dashed border-slate-700">
                        <template x-for="movieId in selected" :key="movieId">
                            <div class="relative group showcase-item"
                                 :data-showcase-movie-id="movieId"
                                 draggable="true"
                                 @dragstart="handleDragStart($event, movieId)"
                                 @dragover.prevent
                                 @drop.prevent="handleDrop($event, movieId)"
                                 @dragend="draggedId = null">
                                <img :src="getMoviePoster(movieId)"
                                     :alt="getMovieTitle(movieId)"
                                     class="w-16 h-24 object-cover rounded-lg shadow-lg">
                                <button type="button"
                                        @click="removeMovie(movieId)"
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full text-white text-xs opacity-0 group-hover:opacity-100 transition">
                                    <i class="fas fa-times"></i>
                                </button>
                                <input type="hidden" name="showcase_movies[]" :value="movieId">
                            </div>
                        </template>

                        <div x-show="selected.length === 0" class="text-slate-500 text-sm flex items-center">
                            <i class="fas fa-arrow-down mr-2"></i> Aşağıdan film seç
                        </div>
                    </div>
                </div>

                {{-- Film Seçici --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-400 mb-3">İzlediğin Filmler</label>

                    <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3 max-h-64 overflow-y-auto p-2">
                        @foreach($availableMovies as $movie)
                            <div @click="toggleMovie({{ $movie->id }})"
                                 :class="selected.includes({{ $movie->id }}) ? 'ring-2 ring-indigo-500 opacity-50' : 'hover:ring-2 hover:ring-slate-500'"
                                 class="cursor-pointer rounded-lg overflow-hidden transition"
                                 data-movie-id="{{ $movie->id }}"
                                 data-movie-title="{{ $movie->title }}"
                                 data-movie-poster="{{ $movie->poster_path ? 'https://image.tmdb.org/t/p/w200' . $movie->poster_path : asset('images/no-poster.png') }}">
                                <img src="{{ $movie->poster_path ? 'https://image.tmdb.org/t/p/w200' . $movie->poster_path : asset('images/no-poster.png') }}"
                                     alt="{{ $movie->title }}"
                                     class="w-full aspect-[2/3] object-cover"
                                     title="{{ $movie->title }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-medium transition">
                    <i class="fas fa-save mr-1"></i> Vitrini Kaydet
                </button>

                @if(session('status') === 'showcase-updated')
                    <span class="text-green-400 text-sm ml-3">Vitrin güncellendi!</span>
                @endif
            </form>
        @endif
    </div>

    {{-- Son Aktiviteler --}}
    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
            <i class="fas fa-clock-rotate-left text-cyan-400"></i> Son Aktiviteler
        </h3>
        <p class="text-sm text-slate-400 mb-6">Hesabında en son yaptığın işlemleri hızlıca gör.</p>

        @if($recentActivities->isEmpty())
            <div class="text-slate-500 text-sm bg-slate-950/70 border border-slate-800 rounded-2xl p-4">
                Henüz görüntülenecek aktivite yok.
            </div>
        @else
            <div class="space-y-3">
                @foreach($recentActivities as $activity)
                    <div class="flex items-start gap-3 p-3 rounded-2xl border border-slate-800 bg-slate-950/60">
                        <div class="w-9 h-9 rounded-xl bg-slate-800 flex items-center justify-center {{ $activity['icon_class'] }}">
                            <i class="fas {{ $activity['icon'] }}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ $activity['title'] }}</p>
                            <p class="text-xs text-slate-400">{{ $activity['description'] }}</p>
                        </div>
                        <span class="text-[11px] text-slate-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($activity['at'])->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Profil Bilgileri --}}
    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <div class="max-w-xl">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <i class="fas fa-user-edit text-indigo-400"></i> Hesap Bilgileri
            </h3>
            <p class="text-sm text-slate-400 mb-6">Hesap bilgilerinizi ve e-posta adresinizi güncelleyin.</p>
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    {{-- Şifre Değiştir --}}
    <div class="p-8 bg-slate-900 border border-slate-800 shadow-2xl rounded-3xl">
        <div class="max-w-xl">
            <h3 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                <i class="fas fa-key text-purple-400"></i> Şifre Değiştir
            </h3>
            <p class="text-sm text-slate-400 mb-6">Güvenliğiniz için uzun ve rastgele bir şifre kullanın.</p>
            @include('profile.partials.update-password-form')
        </div>
    </div>

    {{-- Hesabı Sil --}}
    <div class="p-8 bg-red-950/20 border border-red-900/50 shadow-2xl rounded-3xl">
        <div class="max-w-xl">
            <h3 class="text-lg font-bold text-red-400 mb-2 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i> Hesabı Sil
            </h3>
            <p class="text-sm text-slate-400 mb-6">Hesabınız silindiğinde tüm verileriniz kalıcı olarak kaldırılacaktır.</p>
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</div>

{{-- Alpine.js Avatar Uploader --}}
<script>
/**
 * 📚 AVATAR UPLOADER
 *
 * Akış: Dosya seç → Önizleme → Onayla ve Kaydet
 */
function avatarUploader() {
    return {
        showModal: false,
        previewDataUrl: '',
        selectedFile: null,
        saving: false,
        notice: { show: false, type: 'success', message: '' },

        showNotice(type, message) {
            this.notice = { show: true, type, message };
        },
        
        // Dosya seçildiğinde önizleme modalını aç
        openPreview(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Dosya tipi kontrolü
            if (!file.type.startsWith('image/')) {
                this.showNotice('error', 'Lütfen bir görsel dosyası seçin.');
                return;
            }
            
            // Dosya boyutu kontrolü (max 10MB)
            if (file.size > 10 * 1024 * 1024) {
                this.showNotice('error', 'Dosya boyutu 10MB\'dan küçük olmalı.');
                return;
            }
            
            // Görseli oku ve önizlemeye bas
            const reader = new FileReader();
            reader.onload = (e) => {
                this.selectedFile = file;
                this.previewDataUrl = e.target.result;
                this.showModal = true;
            };
            reader.readAsDataURL(file);
        },
        
        // Modal kapat
        closePreview() {
            this.showModal = false;
            this.previewDataUrl = '';
            this.selectedFile = null;
            this.saving = false;
            // Input'u temizle (aynı dosya tekrar seçilebilsin)
            document.getElementById('avatar-input').value = '';
        },
        
        // Önizlemesi görülen görseli kaydet
        async saveAvatar() {
            if (!this.selectedFile || this.saving) return;
             
            this.saving = true;
             
            try {
                const formData = new FormData();
                formData.append('avatar', this.selectedFile, this.selectedFile.name || 'avatar.jpg');
                formData.append('_token', '{{ csrf_token() }}');

                try {
                    // AJAX ile gönder
                    const response = await fetch('{{ route("profile.avatar.update") }}', {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        // Başarılı - sayfayı yenile
                        window.location.reload();
                    } else {
                        let data = null;
                        try {
                            data = await response.json();
                        } catch (e) {
                            data = null;
                        }
                        this.showNotice('error', data?.message || 'Avatar yüklenirken bir hata oluştu.');
                        this.saving = false;
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    this.showNotice('error', 'Avatar yüklenirken bir hata oluştu.');
                    this.saving = false;
                }
                
            } catch (error) {
                console.error('Crop error:', error);
                this.showNotice('error', 'Görsel işlenirken bir hata oluştu.');
                this.saving = false;
            }
        }
    }
}

function showcaseSelector() {
    return {
        // Mevcut vitrin filmlerini yükle
        selected: @json($user->showcase_movies ? json_decode($user->getRawOriginal('showcase_movies')) : []),
        draggedId: null,

        toggleMovie(movieId) {
            if (this.selected.includes(movieId)) {
                this.removeMovie(movieId);
            } else if (this.selected.length < 5) {
                this.selected.push(movieId);
            }
        },

        removeMovie(movieId) {
            this.selected = this.selected.filter(id => id !== movieId);
        },

        handleDragStart(event, movieId) {
            this.draggedId = movieId;
            event.dataTransfer.effectAllowed = 'move';
        },

        handleDrop(event, targetMovieId) {
            if (!this.draggedId || this.draggedId === targetMovieId) {
                return;
            }

            const fromIndex = this.selected.indexOf(this.draggedId);
            const toIndex = this.selected.indexOf(targetMovieId);
            if (fromIndex === -1 || toIndex === -1) {
                return;
            }

            const reordered = [...this.selected];
            const [moved] = reordered.splice(fromIndex, 1);
            reordered.splice(toIndex, 0, moved);
            this.selected = reordered;
        },

        getMoviePoster(movieId) {
            const el = document.querySelector(`[data-movie-id="${movieId}"]`);
            return el ? el.dataset.moviePoster : '';
        },

        getMovieTitle(movieId) {
            const el = document.querySelector(`[data-movie-id="${movieId}"]`);
            return el ? el.dataset.movieTitle : '';
        }
    }
}
</script>

<style>
    input[type="text"], input[type="email"], input[type="password"] {
        background-color: #020617 !important;
        border-color: #334155 !important;
        color: white !important;
        border-radius: 0.75rem !important;
        padding-left: 1.25rem !important;
        padding-right: 1.25rem !important;
        padding-top: 0.85rem !important;
        padding-bottom: 0.85rem !important;
        width: 100% !important;
        transition: all 0.2s ease-in-out;
    }

    input:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15) !important;
        outline: none !important;
    }

    label {
        color: #94a3b8 !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        tracking: 0.05em !important;
        margin-bottom: 0.5rem !important;
        display: block;
    }

    button[type="submit"] {
        background-color: #4f46e5 !important;
        color: white !important;
        padding: 0.75rem 1.5rem !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        font-size: 0.75rem !important;
        transition: all 0.2s;
    }
    button[type="submit"]:hover {
        background-color: #4338ca !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3) !important;
    }
</style>
@endsection
