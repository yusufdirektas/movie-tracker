{{--
📚 AKTİVİTE KARTI COMPONENT

Kullanım: <x-activity-card :activity="$activity" />

Bu component tek bir aktiviteyi gösterir.
Feed sayfasında ve AJAX infinite scroll'da kullanılır.

@KAVRAM: match() Expression (PHP 8)
- Switch'in modern versiyonu
- Değer döndürür, break gerekmez
- Daha kısa ve temiz syntax

@KAVRAM: Blade Component Props
- :activity="$activity" → PHP değişkeni geçir
- activity="test" → String geçir
--}}

@props(['activity'])

@if($activity->user)
<div class="bg-slate-800/50 backdrop-blur-sm rounded-xl border border-slate-700/50 p-4 hover:border-slate-600/50 transition-colors">
    <div class="flex items-start gap-4">
        {{-- Kullanıcı Avatarı --}}
        <a href="{{ route('users.show', $activity->user) }}" class="flex-shrink-0">
            <img
                src="{{ $activity->user->avatar_url }}"
                alt="{{ $activity->user->name }}"
                class="w-12 h-12 rounded-full object-cover ring-2 ring-slate-700"
            >
        </a>

        {{-- İçerik --}}
        <div class="flex-1 min-w-0">
            {{-- Başlık Satırı --}}
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('users.show', $activity->user) }}" class="font-semibold text-white hover:text-indigo-400 transition-colors">
                    {{ $activity->user->name }}
                </a>

                <span class="text-slate-400">
                    {{ $activity->getTypeIcon() }} {{ $activity->getTypeLabel() }}
                </span>

                {{-- Konu (Film/Kullanıcı/Koleksiyon) --}}
                @if($activity->subject_id)
                    @switch($activity->type)
                        @case(\App\Models\Activity::TYPE_WATCHED)
                        @case(\App\Models\Activity::TYPE_RATED)
                        @case(\App\Models\Activity::TYPE_ADDED_TO_WATCHLIST)
                        @case(\App\Models\Activity::TYPE_COMMENTED)
                            @if($activity->subject)
                                <a href="{{ route('movies.show', $activity->subject_id) }}" class="text-indigo-400 hover:text-indigo-300 font-medium truncate">
                                    {{ $activity->metadata['title'] ?? $activity->subject->title ?? 'Film' }}
                                </a>
                            @elseif(isset($activity->metadata['title']))
                                <span class="text-slate-400 truncate">
                                    {{ $activity->metadata['title'] }}
                                </span>
                            @endif
                            @break

                        @case(\App\Models\Activity::TYPE_FOLLOWED)
                            @if($activity->subject)
                                <a href="{{ route('users.show', $activity->subject_id) }}" class="text-indigo-400 hover:text-indigo-300 font-medium">
                                    {{ $activity->metadata['name'] ?? $activity->subject->name ?? 'Kullanıcı' }}
                                </a>
                            @elseif(isset($activity->metadata['name']))
                                <span class="text-slate-400">
                                    {{ $activity->metadata['name'] }}
                                </span>
                            @endif
                            @break

                        @case(\App\Models\Activity::TYPE_CREATED_COLLECTION)
                            @if($activity->subject)
                                <a href="{{ route('collections.show', $activity->subject_id) }}" class="text-indigo-400 hover:text-indigo-300 font-medium">
                                    {{ $activity->metadata['name'] ?? $activity->subject->name ?? 'Koleksiyon' }}
                                </a>
                            @elseif(isset($activity->metadata['name']))
                                <span class="text-slate-400">
                                    {{ $activity->metadata['name'] }}
                                </span>
                            @endif
                            @break
                    @endswitch
                @endif
            </div>

            {{-- Ek Bilgiler --}}
            @if($activity->type === \App\Models\Activity::TYPE_RATED && isset($activity->metadata['rating']))
                <div class="flex items-center gap-1 mt-1">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-4 h-4 {{ $i <= ($activity->metadata['rating'] / 2) ? 'text-yellow-400' : 'text-slate-600' }}" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @endfor
                    <span class="text-sm text-slate-400 ml-1">
                        {{ number_format($activity->metadata['rating'], 1) }}/10
                    </span>
                </div>
            @endif

            @if($activity->type === \App\Models\Activity::TYPE_COMMENTED && isset($activity->metadata['comment_preview']))
                <p class="text-slate-400 text-sm mt-1 italic">
                    @if($activity->metadata['has_spoiler'] ?? false)
                        <span class="text-red-400">[Spoiler içerir]</span>
                    @else
                        "{{ $activity->metadata['comment_preview'] }}"
                    @endif
                </p>
            @endif

            {{-- Zaman --}}
            <p class="text-slate-500 text-xs mt-2">
                {{ $activity->created_at->diffForHumans() }}
            </p>
        </div>

        {{-- Film Posteri (varsa) --}}
        @if(in_array($activity->type, [\App\Models\Activity::TYPE_WATCHED, \App\Models\Activity::TYPE_RATED, \App\Models\Activity::TYPE_ADDED_TO_WATCHLIST, \App\Models\Activity::TYPE_COMMENTED]))
            @if(isset($activity->metadata['poster_path']) && $activity->metadata['poster_path'])
                <a href="{{ route('movies.show', $activity->subject_id) }}" class="flex-shrink-0">
                    <img
                        src="https://image.tmdb.org/t/p/w92{{ $activity->metadata['poster_path'] }}"
                        alt="{{ $activity->metadata['title'] ?? '' }}"
                        class="w-16 h-24 object-cover rounded-lg shadow-lg hover:scale-105 transition-transform"
                    >
                </a>
            @endif
        @endif
    </div>
</div>
@endif
