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
     * 1.1. Yazım Hatası Duyarlı Akıllı Arama
     *
     * 📚 FUZZY SEARCH STRATEJİSİ:
     * TMDB'nin kendi arama motoru zaten temel typo toleransına sahip.
     * Ama Türkçe karakterler, parantez içi bilgiler ve fazla boşluklar
     * başarısız aramalara yol açabiliyor. Bu metod 4 katmanlı bir
     * strateji ile en iyi eşleşmeyi bulmaya çalışır:
     *
     * Katman 1: Orijinal metin ile ara (TMDB'ye güven)
     * Katman 2: Normalize edilmiş metin ile ara (temizlik + düzeltme)
     * Katman 3: Parantez/sezon bilgisi çıkarılmış çekirdek isimle ara
     * Katman 4: Kelime kelime parçalayıp en uzun eşleşeni bul
     *
     * Hiçbir katman kesin sonuç bulamazsa, toplanan kısmi sonuçları
     * "suggestions" (öneriler) olarak döndürür → "Bunu mu demek istediniz?"
     *
     * @return array{results: array, corrected: bool, corrected_query: string|null, suggestions: array}
     */
    public function smartSearch(string $query): array
    {
        $original = trim($query);
        $suggestions = []; // Tüm katmanlardan toplanan kısmi sonuçlar

        // Katman 1: Orijinal metin ile dene
        $response = $this->searchMovies($original);
        if ($response?->successful()) {
            $results = $response->json()['results'] ?? [];
            if (!empty($results)) {
                return [
                    'results'         => $results,
                    'corrected'       => false,
                    'corrected_query' => null,
                    'suggestions'     => [],
                ];
            }
        }

        // Katman 2: Normalize edilmiş metin ile dene
        $normalized = $this->normalizeQuery($original);
        if ($normalized !== mb_strtolower($original, 'UTF-8')) {
            $response = $this->searchMovies($normalized);
            if ($response?->successful()) {
                $results = $response->json()['results'] ?? [];
                if (!empty($results)) {
                    return [
                        'results'         => $results,
                        'corrected'       => true,
                        'corrected_query' => $normalized,
                        'suggestions'     => [],
                    ];
                }
            }
        }

        // Katman 3: Çekirdek ismi çıkarıp dene
        $core = $this->extractCoreTitle($original);
        if ($core && $core !== $normalized) {
            $response = $this->searchMovies($core);
            if ($response?->successful()) {
                $results = $response->json()['results'] ?? [];
                if (!empty($results)) {
                    return [
                        'results'         => $results,
                        'corrected'       => true,
                        'corrected_query' => $core,
                        'suggestions'     => [],
                    ];
                }
            }
        }

        // Katman 4: Kelime kelime parçala, en uzun anlamlı eşleşmeyi bul
        // Örn: "Matrisx Revolutions 2024" → "Matrisx Revolutions" → "Matrisx" → sonuç?
        $words = explode(' ', $normalized ?: mb_strtolower($original, 'UTF-8'));
        if (count($words) > 1) {
            // Uzundan kısaya doğru dene
            for ($len = count($words); $len >= 1; $len--) {
                $partial = implode(' ', array_slice($words, 0, $len));
                if (mb_strlen($partial) < 2) continue;

                $response = $this->searchMovies($partial);
                if ($response?->successful()) {
                    $results = $response->json()['results'] ?? [];
                    if (!empty($results)) {
                        $suggestions = array_merge($suggestions, array_slice($results, 0, 3));
                        break; // İlk bulunan parçalama yeterli
                    }
                }
            }
        }

        // Tek kelimelik son şans: kelimenin kendisi zaten normalize edilmişse
        // ama hiç sonuç yoksa, kelimeyi kısaltarak dene (son 1-2 harfi at)
        if (empty($suggestions)) {
            $baseWord = $core ?: ($normalized ?: mb_strtolower($original, 'UTF-8'));
            if (mb_strlen($baseWord) > 3) {
                // Son 1 ve 2 harfi atarak dene (typo genelde sonda olur)
                for ($cut = 1; $cut <= 2; $cut++) {
                    $shortened = mb_substr($baseWord, 0, mb_strlen($baseWord) - $cut);
                    if (mb_strlen($shortened) < 2) break;

                    $response = $this->searchMovies($shortened);
                    if ($response?->successful()) {
                        $results = $response->json()['results'] ?? [];
                        if (!empty($results)) {
                            $suggestions = array_slice($results, 0, 3);
                            break;
                        }
                    }
                }
            }
        }

        // Duplicate'ları temizle (aynı film farklı katmanlardan gelebilir)
        $seen = [];
        $uniqueSuggestions = [];
        foreach ($suggestions as $s) {
            if (!in_array($s['id'], $seen)) {
                $seen[] = $s['id'];
                $uniqueSuggestions[] = $s;
            }
        }

        return [
            'results'         => [],
            'corrected'       => false,
            'corrected_query' => null,
            'suggestions'     => array_slice($uniqueSuggestions, 0, 3),
        ];
    }

    /**
     * Arama metnini normalize eder (yazım hatası toleransı için).
     *
     * 📚 NORMALIZASYON ADIMLARI:
     * 1. Küçük harfe çevir
     * 2. Türkçe özel karakterleri ASCII karşılıklarına dönüştür
     *    (ı→i, ş→s, ğ→g, ü→u, ö→o, ç→c, İ→i)
     * 3. Çift boşlukları tek boşluğa indir
     * 4. Özel karakterleri temizle (sadece harf, rakam, boşluk kalsın)
     */
    protected function normalizeQuery(string $query): string
    {
        $query = mb_strtolower($query, 'UTF-8');

        // Türkçe → ASCII dönüşüm tablosu
        $turkishMap = [
            'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u',
            'ö' => 'o', 'ç' => 'c', 'İ' => 'i', 'Ş' => 's',
            'Ğ' => 'g', 'Ü' => 'u', 'Ö' => 'o', 'Ç' => 'c',
            'i̇' => 'i', // Unicode noktalı i
        ];
        $query = str_replace(array_keys($turkishMap), array_values($turkishMap), $query);

        // Özel karakterleri kaldır (harf, rakam, boşluk ve tire hariç)
        $query = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $query);

        // Çoklu boşlukları tek boşluğa indir
        $query = preg_replace('/\s+/', ' ', $query);

        return trim($query);
    }

    /**
     * Parantez içi bilgileri, sezon/bölüm ifadelerini ve ekstra
     * detayları temizleyerek filmin çekirdek ismini çıkarır.
     *
     * 📚 ÖRNEKLER:
     * "Elite (1-6)"              → "elite"
     * "Anne with an E (1-3)"    → "anne with an e"
     * "Sıfır Bir (6 sezon)"     → "sifir bir"
     * "Toy Story 3 - Büyük Kaçış" → "toy story 3"
     */
    protected function extractCoreTitle(string $query): ?string
    {
        $query = mb_strtolower($query, 'UTF-8');

        // Parantez içindekileri kaldır: (2024), (1-3), (1. sezon), vs.
        $query = preg_replace('/\([^)]*\)/', '', $query);

        // Sezon/bölüm ifadelerini kaldır: "1-4sezon", "sezon 1", "1. sezon"
        $query = preg_replace('/\d+[\-–]\d*\s*(?:sezon|bölüm|season|episode)/iu', '', $query);
        $query = preg_replace('/(?:sezon|bölüm|season|episode)\s*\d*/iu', '', $query);

        // Tire sonrasını kaldır (genelde alt başlık): "Toy Story 3 - Büyük Kaçış"
        $query = preg_replace('/\s*[\-–]\s.*$/', '', $query);

        // Normalize et
        $query = $this->normalizeQuery($query);

        // Çok kısa kaldıysa (2 karakterden az) anlamsız
        return mb_strlen($query) >= 2 ? $query : null;
    }

    /**
     * 1.5. Film Arama (Yıla göre daha spesifik)
     */
    public function searchMovie(string $query, ?string $year = null)
    {
        $params = [
            'query'         => $query,
            'include_adult' => false,
        ];

        if ($year) {
            $params['primary_release_year'] = $year;
        }

        return $this->request('/search/movie', $params);
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
