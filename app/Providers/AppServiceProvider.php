<?php

namespace App\Providers;

use App\Models\ImportBatch;
use App\Repositories\Contracts\MovieRepositoryInterface;
use App\Repositories\MovieRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * 📚 SERVICE CONTAINER & DEPENDENCY INJECTION
     *
     * register() metodu, uygulama başlarken çalışır ve "bağımlılıkları" tanımlar.
     *
     * $this->app->bind(Interface, Concrete) ne yapar?
     * - "Biri MovieRepositoryInterface isterse, ona MovieRepository ver" der.
     *
     * Bu neden önemli?
     *
     * 1. LOOSE COUPLING (Gevşek Bağlantı):
     *    Controller → Interface ← Repository
     *    Controller repository'nin detaylarını bilmez, sadece interface'i bilir.
     *
     * 2. TEST EDİLEBİLİRLİK:
     *    Test yazarken: $this->app->bind(Interface, FakeRepository::class);
     *    Gerçek veritabanına dokunmadan test yapılabilir.
     *
     * 3. DEĞİŞTİRİLEBİLİRLİK:
     *    Yarın MongoDB'ye geçmek istersek:
     *    $this->app->bind(Interface, MongoMovieRepository::class);
     *    Sadece bu satır değişir, controller'lar aynı kalır!
     */
    public function register(): void
    {
        // Interface'i concrete class'a bağla
        $this->app->bind(
            MovieRepositoryInterface::class,
            MovieRepository::class
        );
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

        /**
         * 📚 RATE LIMITING (İstek Sınırlama)
         *
         * Rate limiting nedir?
         * - Bir kullanıcının belirli sürede yapabileceği istek sayısını sınırlamak
         *
         * Neden gerekli?
         * 1. DDoS Koruması - Sunucu aşırı yüklenmez
         * 2. Brute Force Koruması - Şifre denemeleri sınırlanır
         * 3. API Abuse Önleme - Botlar sınırsız istek atamaz
         * 4. Kaynak Adaleti - Her kullanıcı eşit kaynak alır
         *
         * Kullanım: Route'larda ->middleware('throttle:limiter-adi')
         */

        // TMDB arama: Toplu import'ta her satır 1 istek → büyük listeler için yeterli olmalı
        RateLimiter::for('api-search', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Film ekleme: Toplu import'ta tüm filmler art arda kaydediliyor → yeterli olmalı
        RateLimiter::for('store-movie', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Koleksiyon işlemleri: Normal kullanımda dakikada 30 yeterli
        RateLimiter::for('collection-mutations', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Takip işlemleri: Spam takip/takipten çık önleme
        RateLimiter::for('follow-actions', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->id ?: $request->ip());
        });

        // Toplu işlemler: Yüksek etkili operasyonlar daha sıkı sınırlanır
        RateLimiter::for('bulk-actions', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Import başlatma: Aynı anda çok fazla import engelenir
        RateLimiter::for('import-start', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Share active import batch with navigation
        View::composer('layouts.navigation', function ($view) {
            $activeImport = null;
            if (Auth::check()) {
                $activeImport = ImportBatch::query()
                    ->where('user_id', Auth::id())
                    ->whereIn('status', ['queued', 'processing'])
                    ->latest()
                    ->first();
            }
            $view->with('activeImport', $activeImport);
        });
    }
}
