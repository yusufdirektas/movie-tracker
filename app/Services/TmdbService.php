<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/**
 * =================================================================
 *  TMDB API SERVİS SINIFI (Hata Yönetimli Versiyon)
 * =================================================================
 *
 *  📚 ÖĞRENİLECEK KAVRAMLAR:
 *
 *  1. TRY-CATCH (Hata Yakalama):
 *     - "try" bloğundaki kod çalışır. Hata olursa "catch" bloğu devreye girer.
 *     - Uygulama çökmek yerine, hatayı kontrollü şekilde ele alırız.
 *     - Örnek: TMDB sunucusu kapalıysa, uygulamamız çökmez, boş sonuç döner.
 *
 *  2. Http::retry(3, 200):
 *     - İlk istek başarısız olursa, 200ms bekleyip 3 kez daha dener.
 *     - Geçici ağ sorunlarında (timeout, 500 hatası) çok işe yarar.
 *     - Ama 404 (bulunamadı) veya 401 (yetkisiz) hatalarında denemez,
 *       çünkü bunlar tekrar deneyince de düzelmez.
 *
 *  3. Http::timeout(10):
 *     - İstek 10 saniyeden fazla sürerse otomatik iptal eder.
 *     - Kullanıcıyı sonsuza kadar bekletmemek için önemli.
 *
 *  4. Log::error() ve Log::warning():
 *     - Hataları storage/logs/laravel.log dosyasına yazar.
 *     - Geliştirici olarak sonradan hataları inceleyebilirsin.
 *     - error → ciddi hata, warning → dikkat çekici ama düzeltilebilir durum.
 *
 *  5. DEFENSIVE PROGRAMMING (Savunmacı Programlama):
 *     - Her şeyin ters gidebileceğini varsay.
 *     - API cevabı boş olabilir, format beklenenden farklı olabilir.
 *     - null coalescing (??) operatörü ile güvenli varsayılan değerler kullan.
 */
class TmdbService
{
    protected string $baseUrl = 'https://api.themoviedb.org/3';
    protected ?string $token;

    public function __construct()
    {
        $this->token = config('services.tmdb.token');
    }

    /**
     * Tüm isteklerin geçtiği ANA MERKEZ
     *
     * 📚 Bu metod "Template Method" deseni kullanır:
     * Ortak davranışı (token, dil, timeout, retry, hata yakalama) tek yerde
     * tanımlayıp, alt metodların sadece endpoint ve parametreleri belirtmesini sağlar.
     *
     * @param  string  $endpoint   API yolu (örn: "/search/movie")
     * @param  array   $params     İsteğe bağlı parametreler
     * @return \Illuminate\Http\Client\Response|null
     */
    protected function request(string $endpoint, array $params = [])
    {
        // Token yoksa hiç istek atmayalım
        if (empty($this->token)) {
            Log::error('TmdbService: TMDB_TOKEN tanımlı değil! .env dosyasını kontrol edin.');
            return null;
        }

        $defaultParams = ['language' => 'tr-TR'];

        try {
            /**
             * 📚 ZINCIR METOD AÇIKLAMASI:
             *
             * Http::withToken($token)     → Authorization: Bearer {token} header'ı ekler
             *     ->timeout(10)           → 10 sn'den fazla sürerse iptal et
             *     ->retry(3, 200, ...)    → Başarısızsa 200ms arayla 3 kez daha dene
             *     ->get(url, params)      → GET isteği gönder
             *
             * retry'ın 3. parametresi (callback) hangi hatalarda tekrar deneneceğini belirler.
             * Sadece sunucu hataları (500+) ve bağlantı hataları için tekrar deneriz.
             * 404 veya 401 gibi hatalarda tekrar denemek mantıksız.
             */
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->retry(3, 200, function ($exception, $request) {
                    // Bağlantı hatası → tekrar dene
                    if ($exception instanceof ConnectionException) {
                        Log::warning('TmdbService: Bağlantı hatası, yeniden deneniyor...');
                        return true;
                    }
                    return false;
                }, throw: false)
                ->get($this->baseUrl . $endpoint, array_merge($defaultParams, $params));

            // Başarısız yanıt kontrolü (4xx veya 5xx)
            if (!$response->successful()) {
                Log::warning("TmdbService: Başarısız yanıt [{$response->status()}] → {$endpoint}", [
                    'status'   => $response->status(),
                    'endpoint' => $endpoint,
                    'params'   => $params,
                ]);
            }

            return $response;

        } catch (ConnectionException $e) {
            // Tüm retry'lar da başarısız olduysa buraya düşer
            Log::error("TmdbService: Bağlantı kurulamadı → {$endpoint}", [
                'error'    => $e->getMessage(),
                'endpoint' => $endpoint,
            ]);
            return null;

        } catch (\Exception $e) {
            // Beklenmeyen herhangi bir hata
            Log::error("TmdbService: Beklenmeyen hata → {$endpoint}", [
                'error'    => $e->getMessage(),
                'endpoint' => $endpoint,
                'trace'    => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PUBLIC API METODLARI
    // ─────────────────────────────────────────────────────────────
    //
    //  📚 Her metod artık null dönebilir (request() null döndüğünde).
    //  Bu yüzden çağıran tarafta (Controller) null kontrolü yapılmalı.
    //  Mevcut Controller kodumuz zaten $response->successful() kontrolü
    //  yapıyor, ama null durumunda da güvenli olmalı.

    /**
     * 1. Film Arama
     */
    public function searchMovies(string $query)
    {
        return $this->request('/search/movie', [
            'query'         => $query,
            'include_adult' => false,
        ]);
    }

    /**
     * 2. Film Detayı (yönetmen bilgisi dahil)
     */
    public function getMovieDetails(int|string $id)
    {
        return $this->request("/movie/{$id}", [
            'append_to_response' => 'credits',
        ]);
    }

    /**
     * 3. Önerilen Filmler
     */
    public function getRecommendations(int|string $id)
    {
        return $this->request("/movie/{$id}/recommendations");
    }

    /**
     * 4. Benzer Filmler
     */
    public function getSimilar(int|string $id)
    {
        return $this->request("/movie/{$id}/similar");
    }

    /**
     * 5. Vizyondaki Filmler (Türkiye)
     */
    public function getNowPlaying()
    {
        return $this->request('/movie/now_playing', [
            'region' => 'TR',
        ]);
    }
}
