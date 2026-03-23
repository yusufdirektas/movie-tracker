<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
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
        /**
         * 📚 STRICT EAGER LOADING (Model::preventLazyLoading)
         * 
         * Bu ne işe yarar?
         * - Geliştirme ortamında (local), bir ilişki Eager Loading olmadan
         *   (yani with() kullanılmadan) çağrılırsa HATA fırlatır.
         * - Bu sayede N+1 query problemlerini geliştirme aşamasında yakalarız.
         * 
         * Örnek:
         *   $movies = Movie::all();          // collections yüklenmedi
         *   foreach ($movies as $movie) {
         *       $movie->collections;          // ❌ HATA! Lazy loading tespit edildi
         *   }
         * 
         * Doğru kullanım:
         *   $movies = Movie::with('collections')->get();
         *   foreach ($movies as $movie) {
         *       $movie->collections;          // ✅ Sorun yok, zaten yüklü
         *   }
         * 
         * NOT: Sadece local ortamda aktif, production'da performansı etkilememesi için kapalı.
         */
        Model::preventLazyLoading(!app()->isProduction());

        /**
         * 📚 PREVENT SILENTLY DISCARDING ATTRIBUTES
         * 
         * Model::preventSilentlyDiscardingAttributes() ne yapar?
         * - $fillable listesinde olmayan bir alan atanmaya çalışılırsa HATA fırlatır.
         * - Geliştirme sırasında yanlışlıkla veri kaybını önler.
         * 
         * Örnek:
         *   Movie::create(['title' => 'Test', 'unknown_field' => 'value']);
         *   // 'unknown_field' $fillable'da yok → sessizce görmezden gelinir
         *   // Bu özellik açıkken → HATA fırlatılır, böylece fark edersin
         */
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());

        // TMDB arama: Toplu import'ta her satır 1 istek → büyük listeler için yeterli olmalı
        RateLimiter::for('api-search', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Film ekleme: Toplu import'ta tüm filmler art arda kaydediliyor → yeterli olmalı
        RateLimiter::for('store-movie', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
