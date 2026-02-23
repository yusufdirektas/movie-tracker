<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Sinefil Arşivi - @yield('title', 'Hoş Geldiniz')</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2220%22 fill=%22%231e1b4b%22/><path d=%22M35 30 L75 50 L35 70 Z%22 fill=%22%236366f1%22/></svg>">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', 'Figtree', sans-serif; }
    </style>
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen antialiased">
    <div class="min-h-screen">

        @include('layouts.navigation')

        @if (View::hasSection('header'))
            <header class="bg-slate-900/50 border-b border-slate-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    @yield('header')
                </div>
            </header>
        @endif

        <main class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                {{-- Başarı Mesajı Bildirimi --}}
                @if(session('success'))
                    <div class="mb-6 bg-emerald-500/10 border border-emerald-500/50 p-4 rounded-xl flex items-center gap-3 text-emerald-400">
                        <i class="fas fa-check-circle"></i> {{ session('success') }}
                    </div>
                @endif

                {{-- Sayfa içerikleri buraya gelecek --}}
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
</body>
</html>
