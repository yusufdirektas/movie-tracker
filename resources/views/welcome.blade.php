<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Tracker - Hoş Geldiniz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#0f172a] text-slate-200 min-h-screen flex flex-col justify-center items-center">

    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-indigo-500/10 blur-[120px] rounded-full"></div>
        <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] bg-purple-500/10 blur-[120px] rounded-full"></div>
    </div>

    <div class="relative z-10 text-center px-4">
        <div class="inline-flex bg-gradient-to-r from-indigo-500 to-purple-500 p-4 rounded-2xl shadow-2xl shadow-indigo-500/20 mb-8">
            <i class="fas fa-film text-white text-5xl"></i>
        </div>

        <h1 class="text-5xl md:text-6xl font-extrabold text-white mb-4 tracking-tight">
            YSD <span class="text-indigo-400">SOFT</span>
        </h1>

        <p class="text-xl text-slate-400 max-w-lg mx-auto mb-12">
            İzlediğin filmleri takip et, arşivini oluştur ve film yolculuğunu kişiselleştir.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/movies') }}" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all transform hover:scale-105 shadow-xl shadow-indigo-600/20">
                        Listeme Git
                    </a>
                @else
                    <a href="{{ route('login') }}" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition-all transform hover:scale-105 shadow-xl shadow-indigo-600/20">
                        Giriş Yap
                    </a>

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="px-8 py-4 bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 rounded-xl font-bold transition-all transform hover:scale-105">
                            Kayıt Ol
                        </a>
                    @endif
                @endauth
            @endif
        </div>
    </div>

    <footer class="absolute bottom-8 text-slate-500 text-sm">
        &copy; {{ date('Y') }} YSD SOFT. Tüm hakları saklıdır.
    </footer>

</body>
</html>
