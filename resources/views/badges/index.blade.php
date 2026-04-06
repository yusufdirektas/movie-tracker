@extends('layouts.app')

@section('title', 'Rozetlerim')

@section('content')
<div class="container mx-auto">

    {{-- HEADER --}}
    <div class="mb-8">
        <h1 class="text-3xl font-black text-white mb-2">
            <i class="fas fa-trophy text-amber-400 mr-2"></i>
            Rozetler
        </h1>
        <p class="text-slate-400">Başarılarını sergilemek için rozetleri kazan!</p>
    </div>

    {{-- İSTATİSTİKLER --}}
    <div class="bg-gradient-to-br from-slate-900/80 to-slate-800/50 border border-slate-700/50 rounded-2xl p-6 mb-8">
        <div class="flex flex-col md:flex-row items-center gap-6">
            {{-- Progress Circle --}}
            <div class="relative w-32 h-32">
                <svg class="w-full h-full transform -rotate-90">
                    <circle cx="64" cy="64" r="56"
                            stroke="currentColor"
                            stroke-width="12"
                            fill="transparent"
                            class="text-slate-700"/>
                    <circle cx="64" cy="64" r="56"
                            stroke="url(#gradient)"
                            stroke-width="12"
                            fill="transparent"
                            stroke-dasharray="352"
                            stroke-dashoffset="{{ 352 - (352 * $stats['percentage'] / 100) }}"
                            stroke-linecap="round"
                            class="transition-all duration-1000"/>
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#f59e0b"/>
                            <stop offset="100%" stop-color="#eab308"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-3xl font-black text-white">{{ $stats['percentage'] }}%</span>
                </div>
            </div>

            {{-- Stats Text --}}
            <div class="flex-1 text-center md:text-left">
                <h2 class="text-2xl font-bold text-white mb-2">
                    {{ $stats['earned'] }} / {{ $stats['total'] }} Rozet
                </h2>
                <p class="text-slate-400">
                    @if($stats['earned'] === 0)
                        Henüz rozet kazanmadın. Film izlemeye, yorum yapmaya ve koleksiyon oluşturmaya başla!
                    @elseif($stats['percentage'] < 50)
                        Harika bir başlangıç! Daha fazla rozet kazanmak için devam et.
                    @elseif($stats['percentage'] < 100)
                        Muhteşem gidiyorsun! Az kaldı tamamlamaya.
                    @else
                        🎉 Tebrikler! Tüm rozetleri kazandın!
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ROZET KATEGORİLERİ --}}
    @foreach($grouped as $category => $items)
        <div class="mb-8">
            <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                @switch($category)
                    @case('Film İzleme')
                        <i class="fas fa-film text-indigo-400"></i>
                        @break
                    @case('Tür Uzmanlığı')
                        <i class="fas fa-masks-theater text-pink-400"></i>
                        @break
                    @case('Eleştirmenlik')
                        <i class="fas fa-pen-fancy text-blue-400"></i>
                        @break
                    @case('Sosyal')
                        <i class="fas fa-users text-green-400"></i>
                        @break
                    @case('Koleksiyon')
                        <i class="fas fa-layer-group text-teal-400"></i>
                        @break
                    @case('Düzenlilik')
                        <i class="fas fa-fire text-orange-400"></i>
                        @break
                    @default
                        <i class="fas fa-award text-amber-400"></i>
                @endswitch
                {{ $category }}
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach($items as $item)
                    <div class="bg-slate-900/70 border border-slate-800 rounded-xl p-4 hover:border-slate-700 transition-colors">
                        <x-badge
                            :badge="$item['badge']"
                            size="md"
                            :showProgress="true"
                            :progress="$item['progress']"
                        />
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

</div>
@endsection
