@extends('layouts.app')

@section('title', 'Kullanıcı Keşfet')

@section('content')
<div class="container mx-auto">

    {{-- BAŞLIK --}}
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight">
            <i class="fas fa-users text-indigo-400 mr-3"></i>
            Kullanıcı <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Keşfet</span>
        </h1>
        <p class="text-slate-500 mt-2">Film zevkini paylaşan insanları bul ve takip et</p>
    </div>

    {{-- ARAMA --}}
    <div class="mb-8">
        <form action="{{ route('users.index') }}" method="GET" class="relative max-w-md">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fas fa-search text-slate-500"></i>
            </div>
            <input type="text" name="search" value="{{ $search }}"
                placeholder="İsim veya email ile ara..."
                class="w-full pl-11 pr-4 py-3 bg-slate-900 border border-slate-700 text-white rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder-slate-600">
            @if($search)
                <a href="{{ route('users.index') }}"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-500 hover:text-white">
                    <i class="fas fa-times-circle"></i>
                </a>
            @endif
        </form>
    </div>

    {{-- KULLANICI LİSTESİ --}}
    @if($users->isEmpty())
        <div class="text-center py-16 bg-slate-900/50 rounded-3xl border border-slate-800">
            <i class="fas fa-user-slash text-4xl text-slate-700 mb-4"></i>
            <p class="text-slate-500">
                @if($search)
                    "{{ $search }}" için kullanıcı bulunamadı.
                @else
                    Henüz keşfedilecek kullanıcı yok.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($users as $user)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 hover:border-indigo-500/50 transition-all group">
                    {{-- Kullanıcı Bilgisi --}}
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-xl font-black">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('users.show', $user) }}"
                                class="text-white font-bold text-lg truncate block hover:text-indigo-400 transition-colors">
                                {{ $user->name }}
                            </a>
                            <p class="text-slate-500 text-sm truncate">{{ $user->email }}</p>
                        </div>
                    </div>

                    {{-- İstatistikler --}}
                    <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                        <div class="bg-slate-800/50 rounded-xl py-2">
                            <div class="text-white font-bold">{{ $user->movies_count }}</div>
                            <div class="text-slate-500 text-[10px] uppercase tracking-wider">Film</div>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl py-2">
                            <div class="text-white font-bold">{{ $user->followers_count }}</div>
                            <div class="text-slate-500 text-[10px] uppercase tracking-wider">Takipçi</div>
                        </div>
                        <div class="bg-slate-800/50 rounded-xl py-2">
                            <div class="text-white font-bold">{{ $user->following_count }}</div>
                            <div class="text-slate-500 text-[10px] uppercase tracking-wider">Takip</div>
                        </div>
                    </div>

                    {{-- Takip Butonu (AJAX) --}}
                    <div x-data="{
                        isFollowing: {{ in_array($user->id, $followingIds) ? 'true' : 'false' }},
                        loading: false,
                        async toggle() {
                            if (this.loading) return;
                            this.loading = true;
                            try {
                                const url = this.isFollowing
                                    ? '{{ route('users.unfollow', $user) }}'
                                    : '{{ route('users.follow', $user) }}';
                                const response = await fetch(url, {
                                    method: this.isFollowing ? 'DELETE' : 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                    }
                                });
                                if (response.ok) this.isFollowing = !this.isFollowing;
                            } catch (e) { console.error(e); }
                            finally { this.loading = false; }
                        }
                    }">
                        <button @click="toggle()" :disabled="loading"
                            :class="isFollowing
                                ? 'bg-slate-800 text-slate-400 hover:bg-red-500/20 hover:text-red-400 border border-slate-700 hover:border-red-500/50'
                                : 'bg-indigo-500 text-white hover:bg-indigo-600'"
                            class="w-full py-2.5 rounded-xl font-bold text-sm transition-all disabled:opacity-50">
                            <i :class="loading ? 'fas fa-spinner fa-spin' : (isFollowing ? 'fas fa-user-check' : 'fas fa-user-plus')" class="mr-2"></i>
                            <span x-text="isFollowing ? 'Takip Ediliyor' : 'Takip Et'"></span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- SAYFALAMA --}}
        <div class="mt-8">
            {{ $users->withQueryString()->links() }}
        </div>
    @endif

</div>
@endsection
