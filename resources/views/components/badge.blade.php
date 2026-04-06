{{--
📚 ROZET COMPONENT

Kullanım:
  <x-badge :badge="$badge" />
  <x-badge :badge="$badge" size="lg" />
  <x-badge :badge="$badge" :showProgress="true" :progress="$progress" />

@KAVRAM: Blade Component Props
- :badge="$badge" → PHP değişkeni geçir
- size="sm" → String geçir
- :showProgress="true" → Boolean geçir

Props:
- badge: Badge model
- size: 'sm', 'md', 'lg' (varsayılan: 'md')
- showProgress: İlerleme göster (varsayılan: false)
- progress: İlerleme verisi [current, target, percentage]
- earned: Kazanıldı mı? (varsayılan: badge üzerinden kontrol)
--}}

@props([
    'badge',
    'size' => 'md',
    'showProgress' => false,
    'progress' => null,
    'earned' => null
])

@php
    // Progress null olabilir, güvenli erişim
    $isEarned = $earned ?? ($progress ? ($progress['is_earned'] ?? false) : false);

    $sizeClasses = match($size) {
        'sm' => 'w-12 h-12 text-xl',
        'md' => 'w-16 h-16 text-2xl',
        'lg' => 'w-20 h-20 text-3xl',
        default => 'w-16 h-16 text-2xl',
    };

    $containerClasses = match($size) {
        'sm' => 'p-2',
        'md' => 'p-3',
        'lg' => 'p-4',
        default => 'p-3',
    };
@endphp

<div class="flex flex-col items-center {{ $containerClasses }}"
     x-data="{ showTooltip: false }"
     @mouseenter="showTooltip = true"
     @mouseleave="showTooltip = false">

    {{-- Rozet İkonu --}}
    <div class="relative">
        <div class="{{ $sizeClasses }} rounded-full flex items-center justify-center
                    {{ $isEarned
                        ? 'bg-gradient-to-br ' . $badge->getColorClass() . ' shadow-lg'
                        : 'bg-slate-700/50 grayscale' }}"
             role="img"
             aria-label="{{ $badge->name }}">
            <span class="{{ !$isEarned ? 'opacity-50' : '' }}">
                {{ $badge->icon }}
            </span>
        </div>

        {{-- Kazanıldı işareti --}}
        @if($isEarned)
            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
        @endif
    </div>

    {{-- Rozet Adı (sadece md ve lg için) --}}
    @if($size !== 'sm')
        <p class="mt-2 text-sm font-medium {{ $isEarned ? 'text-white' : 'text-slate-400' }} text-center">
            {{ $badge->name }}
        </p>
    @endif

    {{-- İlerleme Çubuğu --}}
    @if($showProgress && $progress && !$isEarned)
        <div class="w-full mt-2">
            <div class="h-1.5 bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r {{ $badge->getColorClass() }} transition-all duration-500"
                     style="width: {{ $progress['percentage'] }}%"></div>
            </div>
            <p class="text-xs text-slate-500 text-center mt-1">
                {{ $progress['current'] }}/{{ $progress['target'] }}
            </p>
        </div>
    @endif

    {{-- Tooltip --}}
    <div x-show="showTooltip"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute z-50 mt-2 px-3 py-2 bg-slate-900 border border-slate-700 rounded-lg shadow-xl text-center max-w-xs"
         style="display: none;">
        <p class="font-semibold text-white text-sm">{{ $badge->name }}</p>
        <p class="text-slate-400 text-xs mt-1">{{ $badge->description }}</p>
        @if(!$isEarned)
            <p class="text-indigo-400 text-xs mt-1 font-medium">
                {{ $badge->getRequirementText() }}
            </p>
        @else
            <p class="text-green-400 text-xs mt-1">✓ Kazanıldı</p>
        @endif
    </div>
</div>
