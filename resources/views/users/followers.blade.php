@extends('layouts.app')

@section('title', $user->name . ' - Takipçiler')

@section('content')
<div class="container mx-auto max-w-3xl">

    {{-- GERİ BUTONU --}}
    <a href="{{ route('users.show', $user) }}"
        class="inline-flex items-center gap-2 text-slate-500 hover:text-white transition-colors mb-6">
        <i class="fas fa-arrow-left"></i>
        <span>{{ $user->name }} profiline dön</span>
    </a>

    {{-- BAŞLIK --}}
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl font-black text-white tracking-tight">
            <i class="fas fa-users text-indigo-400 mr-3"></i>
            Takipçiler
        </h1>
        <p class="text-slate-500 mt-1">{{ $user->name }} kullanıcısını takip edenler</p>
    </div>

    @if($followers->isEmpty())
        <div class="text-center py-16 bg-slate-900/50 rounded-3xl border border-slate-800">
            <i class="fas fa-user-slash text-4xl text-slate-700 mb-4"></i>
            <p class="text-slate-500">Henüz takipçi yok.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($followers as $follower)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex items-center gap-4 hover:border-slate-700 transition-all">
                    {{-- Avatar --}}
                    <a href="{{ route('users.show', $follower) }}"
                        class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                        {{ strtoupper(substr($follower->name, 0, 1)) }}
                    </a>

                    {{-- Bilgi --}}
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('users.show', $follower) }}"
                            class="text-white font-bold hover:text-indigo-400 transition-colors truncate block">
                            {{ $follower->name }}
                        </a>
                        <p class="text-slate-500 text-sm">{{ $follower->movies_count }} film izledi</p>
                    </div>

                    {{-- Takip Butonu (AJAX) --}}
                    @if($follower->id !== auth()->id())
                        <div x-data="{
                            isFollowing: {{ auth()->user()->isFollowing($follower) ? 'true' : 'false' }},
                            loading: false,
                            async toggle() {
                                if (this.loading) return;
                                this.loading = true;
                                try {
                                    const url = this.isFollowing
                                        ? '{{ route('users.unfollow', $follower) }}'
                                        : '{{ route('users.follow', $follower) }}';
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
                                class="px-4 py-2 rounded-xl text-sm font-bold transition-all disabled:opacity-50">
                                <i :class="loading ? 'fas fa-spinner fa-spin' : (isFollowing ? 'fas fa-user-check' : 'fas fa-user-plus')" class="mr-1"></i>
                                <span x-text="isFollowing ? 'Takip' : 'Takip Et'"></span>
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- SAYFALAMA --}}
        <div class="mt-8">
            {{ $followers->links() }}
        </div>
    @endif

</div>
@endsection
