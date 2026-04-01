<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sinefil Arşivi - @yield('title', 'Hoş Geldiniz')</title>

    {{-- Favicon --}}
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%231e1b4b%22/><path d=%22M35 30 L75 50 L35 70 Z%22 fill=%22%236366f1%22/></svg>">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Vite: CSS & JS (Alpine.js app.js içinde import ediliyor) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', 'Figtree', sans-serif; }
    </style>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen antialiased">
    {{-- 
        📚 SKIP TO CONTENT LINK
        
        Erişilebilirlik özelliği: Klavye kullanıcıları Tab tuşuyla gezinirken
        bu link görünür olur ve menüyü atlayarak direkt içeriğe gitmelerini sağlar.
        
        sr-only: Görünmez ama ekran okuyucu okur
        focus:not-sr-only: Tab ile odaklandığında görünür olur
    --}}
    <a href="#main-content" 
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[9999] focus:bg-indigo-600 focus:text-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-xl focus:outline-none">
        İçeriğe Atla
    </a>

    <div class="min-h-screen">

        {{-- Ana Menü (İzleme Listem burada yer alacak) --}}
        @include('layouts.navigation')

        {{-- Sayfa Başlığı Alanı --}}
        @if (View::hasSection('header'))
            <header class="bg-slate-900/50 border-b border-slate-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif

        {{-- Ana İçerik Alanı --}}
        <main id="main-content" class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

                {{-- Başarı / Hata Bildirimleri --}}
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-transition x-cloak
                        role="status" aria-live="polite"
                        class="mb-6 bg-emerald-500/10 border border-emerald-500/50 p-4 rounded-xl text-emerald-300">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-check-circle mt-0.5"></i>
                            <div class="flex-1">
                                <p class="font-semibold">{{ session('success') }}</p>
                            </div>
                            <button type="button" @click="show = false" class="text-emerald-300/70 hover:text-emerald-200 transition-colors" aria-label="Başarı mesajını kapat">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" x-transition x-cloak
                        role="alert" aria-live="assertive"
                        class="mb-6 bg-red-500/10 border border-red-500/50 p-4 rounded-xl text-red-300">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-circle mt-0.5"></i>
                            <div class="flex-1">
                                <p class="font-semibold">{{ session('error') }}</p>
                                @if(session('error_action'))
                                    <p class="text-xs text-red-200/80 mt-1">{{ session('error_action') }}</p>
                                @endif
                            </div>
                            <button type="button" @click="show = false" class="text-red-300/70 hover:text-red-200 transition-colors" aria-label="Hata mesajını kapat">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('info'))
                    <div x-data="{ show: true }" x-show="show" x-transition x-cloak
                        role="status" aria-live="polite"
                        class="mb-6 bg-sky-500/10 border border-sky-500/50 p-4 rounded-xl text-sky-300">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle mt-0.5"></i>
                            <div class="flex-1">
                                <p class="font-semibold">{{ session('info') }}</p>
                                @if(session('info_action'))
                                    <p class="text-xs text-sky-200/80 mt-1">{{ session('info_action') }}</p>
                                @endif
                            </div>
                            <button type="button" @click="show = false" class="text-sky-300/70 hover:text-sky-200 transition-colors" aria-label="Bilgi mesajını kapat">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Dinamik İçerik --}}
                @yield('content')

            </div>
        </main>
    </div>

    {{-- Footer --}}
    <footer class="border-t border-slate-800 mt-20 py-10">
        <div class="container mx-auto px-4 text-center">
            <p class="text-slate-500 text-sm font-medium">
                &copy; {{ date('Y') }} Film Arşivim. Tüm hakları saklıdır.
            </p>
            <p class="text-slate-600 text-xs mt-2">
                Developed by <span class="text-indigo-500 font-black hover:text-indigo-400 transition-colors cursor-default">YSD</span>
            </p>
        </div>
    </footer>

    @stack('scripts')

    {{-- Global Import Progress Watcher --}}
    @auth
        @if($activeImport ?? null)
            <div x-data="importWatcher({{ $activeImport->id }}, {{ $activeImport->total_items }})"
                 x-init="startWatching()"
                 class="hidden"></div>
            <script>
            function importWatcher(batchId, totalItems) {
                return {
                    batchId,
                    totalItems,
                    lastProcessed: 0,
                    pollInterval: null,

                    async startWatching() {
                        // Request notification permission on first load
                        if ('Notification' in window && Notification.permission === 'default') {
                            await Notification.requestPermission();
                        }

                        this.pollInterval = setInterval(() => this.checkStatus(), 5000);
                    },

                    async checkStatus() {
                        try {
                            const res = await fetch(`/movies/import-list/${this.batchId}/status`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            if (!res.ok) return;

                            const data = await res.json();
                            this.lastProcessed = data.batch.processed_items;

                            if (data.batch.status === 'finished') {
                                this.stopWatching();
                                this.notifyComplete(data.batch);
                                this.updateNavbarBadge(null);
                            } else {
                                this.updateNavbarBadge(data.batch);
                            }
                        } catch (_) {}
                    },

                    stopWatching() {
                        if (this.pollInterval) {
                            clearInterval(this.pollInterval);
                            this.pollInterval = null;
                        }
                    },

                    notifyComplete(batch) {
                        const title = 'İçe Aktarma Tamamlandı!';
                        const body = `${batch.success_items} başarılı, ${batch.duplicate_items} duplicate, ${batch.error_items + batch.not_found_items} hata`;

                        // Browser notification
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification(title, {
                                body: body,
                                icon: '/favicon.ico',
                                tag: 'import-complete'
                            });
                        }

                        // In-page toast
                        this.showToast(title, body, batch);
                    },

                    showToast(title, body, batch) {
                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 z-50 bg-emerald-600 text-white px-6 py-4 rounded-2xl shadow-2xl max-w-sm animate-slide-up';
                        toast.innerHTML = `
                            <div class="flex items-start gap-3">
                                <i class="fas fa-check-circle text-2xl"></i>
                                <div class="flex-1">
                                    <p class="font-bold">${title}</p>
                                    <p class="text-sm text-emerald-100 mt-1">${body}</p>
                                    <a href="/movies/import-list/history" class="inline-block mt-2 text-xs underline hover:no-underline">Detayları Gör</a>
                                </div>
                                <button onclick="this.closest('div.fixed').remove()" class="text-emerald-200 hover:text-white">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        setTimeout(() => toast.remove(), 10000);
                    },

                    updateNavbarBadge(batch) {
                        const badge = document.querySelector('[data-import-badge]');
                        if (!badge) return;

                        if (!batch) {
                            badge.style.display = 'none';
                        } else {
                            const counter = badge.querySelector('[data-import-counter]');
                            if (counter) {
                                counter.textContent = `${batch.processed_items}/${batch.total_items}`;
                            }
                        }
                    }
                };
            }
            </script>
            <style>
            @keyframes slide-up {
                from { transform: translateY(100%); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .animate-slide-up { animation: slide-up 0.3s ease-out; }
            </style>
        @endif
    @endauth
</body>
</html>
