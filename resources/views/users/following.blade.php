@extends('layouts.app')

@section('title', $user->name . ' - Takip Edilenler')

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
            <i class="fas fa-user-friends text-indigo-400 mr-3"></i>
            Takip Edilenler
        </h1>
        <p class="text-slate-500 mt-1">{{ $user->name }} kullanıcısının takip ettikleri</p>
    </div>

    @if($following->isEmpty())
        <div class="text-center py-16 bg-slate-900/50 rounded-3xl border border-slate-800">
            <i class="fas fa-user-slash text-4xl text-slate-700 mb-4"></i>
            <p class="text-slate-500">Henüz kimseyi takip etmiyor.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($following as $followed)
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 flex items-center gap-4 hover:border-slate-700 transition-all">
                    {{-- Avatar --}}
                    <a href="{{ route('users.show', $followed) }}"
                        class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                        {{ strtoupper(substr($followed->name, 0, 1)) }}
                    </a>

                    {{-- Bilgi --}}
                    <div class="flex-1 min-w-0">
                        <a href="{{ route('users.show', $followed) }}"
                            class="text-white font-bold hover:text-indigo-400 transition-colors truncate block">
                            {{ $followed->name }}
                        </a>
                        <p class="text-slate-500 text-sm">{{ $followed->movies_count }} film izledi</p>
                    </div>

                    {{-- Takip Butonu --}}
                    @if($followed->id !== auth()->id())
                        @if(auth()->user()->isFollowing($followed))
                            <form action="{{ route('users.unfollow', $followed) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl text-sm font-bold bg-slate-800 text-slate-400 hover:bg-red-500/20 hover:text-red-400 border border-slate-700 hover:border-red-500/50 transition-all">
                                    <i class="fas fa-user-check mr-1"></i> Takip
                                </button>
                            </form>
                        @else
                            <form action="{{ route('users.follow', $followed) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 rounded-xl text-sm font-bold bg-indigo-500 text-white hover:bg-indigo-600 transition-all">
                                    <i class="fas fa-user-plus mr-1"></i> Takip Et
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>

        {{-- SAYFALAMA --}}
        <div class="mt-8">
            {{ $following->links() }}
        </div>
    @endif

</div>
@endsection
