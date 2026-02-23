@extends('layouts.app')

@section('title', 'Sana Özel Öneriler')

@section('content')
<div class="container mx-auto py-8">
    <div class="mb-12 flex items-center justify-between">
        <h1 class="text-4xl font-extrabold text-white tracking-tight italic flex items-center gap-3">
            Sana Özel <span class="text-indigo-500">Öneriler</span> 🍿
        </h1>
        <a href="{{ route('movies.index') }}" class="text-slate-500 hover:text-indigo-400 font-bold uppercase tracking-widest text-xs transition-colors border-b border-transparent hover:border-indigo-400 pb-1">
            <i class="fas fa-arrow-left mr-1"></i> Arşive Dön
        </a>
    </div>

    @if(count($recommendations) > 0)
        <div class="mb-10 bg-slate-900/80 border border-indigo-500/30 p-6 rounded-3xl shadow-lg backdrop-blur-sm flex flex-col sm:flex-row items-center gap-4">
            <div class="w-14 h-14 bg-indigo-500/20 rounded-2xl flex shrink-0 items-center justify-center text-indigo-400">
                <i class="fas fa-magic text-2xl"></i>
            </div>
            <div>
                <p class="text-slate-300 text-sm leading-relaxed">
                    Listene eklediğin son film olan <strong class="text-white">"{{ $lastMovie->title }}"</strong> baz alınarak senin için bu filmleri seçtik. Belki hoşuna gider!
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
            @foreach($recommendations as $rec)
                <div class="group relative bg-slate-900 border border-slate-800/50 rounded-3xl overflow-hidden transition-all hover:scale-105 hover:shadow-2xl hover:shadow-indigo-500/10 hover:border-indigo-500/30 flex flex-col h-full">

                    <div class="aspect-[2/3] relative overflow-hidden bg-slate-800 shrink-0">
                        @if(isset($rec['poster_path']) && $rec['poster_path'])
                            <img src="https://image.tmdb.org/t/p/w500{{ $rec['poster_path'] }}" alt="{{ $rec['title'] }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-700 bg-slate-950">
                                <i class="fas fa-image text-4xl"></i>
                            </div>
                        @endif

                        <div class="absolute top-3 left-3 z-10">
                            <div class="bg-black/80 backdrop-blur-md text-white px-2 py-1 rounded-lg flex items-center gap-1 border border-white/10 shadow-lg">
                                <i class="fas fa-star text-yellow-400 text-[10px]"></i>
                                <span class="text-xs font-black">{{ number_format($rec['vote_average'] ?? 0, 1) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 flex flex-col flex-grow justify-between">
                        <h4 class="text-white font-bold text-sm mb-2 line-clamp-2" title="{{ $rec['title'] }}">
                            {{ $rec['title'] }}
                        </h4>

                        <div class="flex justify-between items-center mt-auto pt-3 border-t border-slate-800/50">
                            <span class="text-slate-500 text-xs font-semibold">
                                {{ isset($rec['release_date']) && strlen($rec['release_date']) > 4 ? substr($rec['release_date'], 0, 4) : '-' }}
                            </span>

                            <form action="{{ route('movies.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tmdb_id" value="{{ $rec['id'] }}">
                                <input type="hidden" name="is_watched" value="0">
                                <button type="submit" class="text-indigo-400 hover:text-white bg-indigo-500/10 hover:bg-indigo-500 px-2 py-1 rounded text-[10px] font-bold uppercase transition-colors" title="İzleneceklere Ekle">
                                    <i class="fas fa-plus mr-1"></i> Ekle
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

    @else
        <div class="bg-slate-900 border-2 border-dashed border-slate-800 rounded-[2.5rem] p-20 text-center">
            <div class="bg-slate-800 w-20 h-20 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                <i class="fas fa-ghost text-3xl text-slate-600"></i>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">Henüz öneri bulamadık</h3>
            <p class="text-slate-500 mb-8 italic text-sm">Sistemin seni tanıması ve sana harika filmler önerebilmesi için arşivine birkaç film eklemelisin.</p>
            <a href="{{ route('movies.create') }}" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-4 rounded-xl font-bold transition-all inline-block shadow-lg shadow-indigo-600/20">
                + Film Ekle
            </a>
        </div>
    @endif
</div>
@endsection
