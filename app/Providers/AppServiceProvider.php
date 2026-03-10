<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // TMDB arama endpoint'i: Kullanıcı başına dakikada 30 istek
        // Debounce ile bile hızlı yazan biri 30'u geçebilir
        RateLimiter::for('api-search', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Film ekleme endpoint'i: Kullanıcı başına dakikada 10 istek
        // Normal kullanımda 10'u geçmek neredeyse imkansız
        RateLimiter::for('store-movie', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
