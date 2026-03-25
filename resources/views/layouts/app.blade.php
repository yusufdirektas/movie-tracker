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
        <main class="py-12">
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
</body>
</html>
