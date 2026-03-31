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
     * 1.2. Gelişmiş Film Arama (Discover API)
     *
     * 📚 TMDB DISCOVER API
     *
     * /search/movie → Sadece isimle arama yapar
     * /discover/movie → Yıl, tür, puan, süre gibi filtrelerle arama yapar
     *
     * Bu metod, kullanıcının gelişmiş filtrelerle TMDB'de film keşfetmesini sağlar.
     * Tüm parametreler opsiyoneldir - sadece dolu olanlar gönderilir.
     *
     * @param array $filters Filtre parametreleri:
     *   - query: string|null     → Film adı (varsa /search, yoksa /discover kullanılır)
     *   - year: int|null         → Çıkış yılı (tam eşleşme)
     *   - year_from: int|null    → Minimum çıkış yılı
     *   - year_to: int|null      → Maksimum çıkış yılı
     *   - genre: int|null        → Tür ID'si (TMDB genre ID)
     *   - min_rating: float|null → Minimum TMDB puanı (0-10)
     *   - sort_by: string|null   → Sıralama (popularity.desc, vote_average.desc, vb.)
     *
     * @return \Illuminate\Http\Client\Response|null
     *
     * Kullanım:
     *   $tmdb->discoverMovies(['year_from' => 2020, 'min_rating' => 7.0]);
     *   $tmdb->discoverMovies(['query' => 'Batman', 'year' => 2022]);
     *   $tmdb->discoverMovies(['genre' => 28, 'page' => 2]); // 2. sayfa
     */
    public function discoverMovies(array $filters = [])
    {
        // Eğer query varsa, önce search endpoint'i kullan ve sonuçları filtrele
        // Query yoksa discover endpoint'i kullan
        $hasQuery = !empty($filters['query']);

        // 📚 SAYFALAMA (Pagination)
        // TMDB her sayfada 20 sonuç döner. page parametresi ile sonraki sayfaları alabiliriz.
        $page = $filters['page'] ?? 1;

        if ($hasQuery) {
            // Search endpoint + client-side filtreleme
            // (TMDB search API filtreleme desteklemiyor, sadece query alıyor)
            return $this->request('/search/movie', [
                'query'         => $filters['query'],
                'include_adult' => false,
                'year'          => $filters['year'] ?? null, // Search API sadece year destekler
                'page'          => $page,
            ]);
        }

        // Discover endpoint - tüm filtreleri destekler
        $params = [
            'include_adult'  => false,
            'sort_by'        => $filters['sort_by'] ?? 'popularity.desc',
            'vote_count.gte' => 50, // Çok az oy alan filmleri hariç tut
            'page'           => $page,
        ];

        // Yıl filtreleri
        if (!empty($filters['year'])) {
            $params['primary_release_year'] = $filters['year'];
        }
        if (!empty($filters['year_from'])) {
            $params['primary_release_date.gte'] = $filters['year_from'] . '-01-01';
        }
        if (!empty($filters['year_to'])) {
            $params['primary_release_date.lte'] = $filters['year_to'] . '-12-31';
        }

        // Tür filtresi (TMDB genre ID)
        if (!empty($filters['genre'])) {
            $params['with_genres'] = $filters['genre'];
        }

        // Puan filtresi
        if (!empty($filters['min_rating'])) {
            $params['vote_average.gte'] = $filters['min_rating'];
        }

        // Süre filtresi (dakika)
        if (!empty($filters['runtime_min'])) {
            $params['with_runtime.gte'] = $filters['runtime_min'];
        }
        if (!empty($filters['runtime_max'])) {
            $params['with_runtime.lte'] = $filters['runtime_max'];
        }

        return $this->request('/discover/movie', $params);
    }

    /**
     * 1.3. TMDB Tür Listesini Getir
     *
     * 📚 Genre ID'leri sabit olduğu için cache'lenebilir.
     * UI'da dropdown için kullanılır.
     *
     * @return array Tür listesi [{id: 28, name: "Aksiyon"}, ...]
     */
    public function getGenres(): array
    {
        $response = $this->request('/genre/movie/list');

        if ($response?->successful()) {
            return $response->json()['genres'] ?? [];
        }

        // Fallback: En yaygın türler (API başarısız olursa)
        return [
            ['id' => 28, 'name' => 'Aksiyon'],
            ['id' => 12, 'name' => 'Macera'],
            ['id' => 16, 'name' => 'Animasyon'],
            ['id' => 35, 'name' => 'Komedi'],
            ['id' => 80, 'name' => 'Suç'],
            ['id' => 99, 'name' => 'Belgesel'],
            ['id' => 18, 'name' => 'Dram'],
            ['id' => 10751, 'name' => 'Aile'],
            ['id' => 14, 'name' => 'Fantastik'],
            ['id' => 36, 'name' => 'Tarih'],
            ['id' => 27, 'name' => 'Korku'],
            ['id' => 10402, 'name' => 'Müzik'],
            ['id' => 9648, 'name' => 'Gizem'],
            ['id' => 10749, 'name' => 'Romantik'],
            ['id' => 878, 'name' => 'Bilim Kurgu'],
            ['id' => 53, 'name' => 'Gerilim'],
            ['id' => 10752, 'name' => 'Savaş'],
            ['id' => 37, 'name' => 'Western'],
        ];
    }

    /**
     * 1.1. Akıllı Arama Motoru v3 – Başlık Doğrulamalı + Dil Filtreleme
     *
     * 📚 KÖK SORUNLAR VE ÇÖZÜMLER:
     *
     * Sorun 1: "Flipped" → "Flip Flappers" (anime) kabul ediliyordu
     *   Çözüm: findBestMatch() ile başlık benzerlik skoru kontrol ediliyor.
     *   Benzerlik eşiğini geçemeyen sonuçlar reddedilir.
     *
     * Sorun 2: "Atonement" → Japonca anime, "Dhom 3" → Fransızca film
     *   Çözüm: Sonuçlar dil filtresinden geçiriliyor. TR/EN öncelikli.
     *   Diğer diller yalnızca başlık benzerliği yüksekse kabul edilir.
     *
     * Sorun 3: "Joker" → "Impractical Jokers" (popülerlik sıralaması yanlış seçtiriyordu)
     *   Çözüm: Popülerlik yerine başlık benzerlik skoru ile en iyi eşleşme seçiliyor.
     *   "Joker" tam eşleşme 1.0 > "Impractical Jokers" kısmi eşleşme 0.6
     *
     * Sorun 4: "Bridge yo terabithia" → Korece filmler
     *   Çözüm: Kısa anlamsız kelimeler (2 harf, sayı olmayan) kaldırılıp tekrar aranıyor.
     *   "bridge terabithia" → "Bridge to Terabithia" bulunuyor.
     *
     * KATMAN YAPISI:
     * 1. Orijinal metin → ara + başlık doğrula
     * 2. Normalize metin (Türkçe→ASCII) → ara + başlık doğrula
     * 3. Çekirdek isim (parantez/sezon temizliği) → ara + başlık doğrula
     * 4. Kısa kelimeler çıkarılmış → ara + başlık doğrula
     * 5. Typo varyantları → ara + başlık doğrula
     * 6. Kelime parçalama → öneri toplama
     * 7. Karakter kırpma → son şans önerileri
     *
     * Her katmanda: arama sonuçları + findBestMatch ile en iyi eşleşme seçilir.
     * Bulunamazsa → toplanıp suggestions olarak döner (dil filtreleme + popülerlik)
     *
     * @return array{results: array, corrected: bool, corrected_query: string|null, suggestions: array}
     */
    public function smartSearch(string $query): array
    {
        $original = trim($query);
        $allCandidates = [];

        // ── Katman 1: Orijinal metin ──
        $results = $this->searchBothLanguages($original);
        $best = $this->findBestMatch($original, $results);
        if ($best) {
            return $this->matchResult([$best]);
        }
        $allCandidates = array_merge($allCandidates, $results);

        // ── Katman 2: Normalize (Türkçe→ASCII, özel karakter temizliği) ──
        $normalized = $this->normalizeQuery($original);
        if ($normalized !== mb_strtolower($original, 'UTF-8')) {
            $results = $this->searchBothLanguages($normalized);
            $best = $this->findBestMatch($original, $results);
            if ($best) {
                return $this->matchResult([$best], true, $normalized);
            }
            $allCandidates = array_merge($allCandidates, $results);
        }

        // ── Katman 3: Çekirdek isim (parantez, sezon kaldırılmış) ──
        $core = $this->extractCoreTitle($original);
        if ($core && $core !== ($normalized ?: '')) {
            $results = $this->searchBothLanguages($core);
            $best = $this->findBestMatch($original, $results);
            if ($best) {
                return $this->matchResult([$best], true, $core);
            }
            $allCandidates = array_merge($allCandidates, $results);
        }

        // ── Katman 4: Kısa anlamsız kelimeler çıkarılmış ──
        // "Bridge yo terabithia" → "bridge terabithia" → TMDB bulur
        $baseText = $core ?: ($normalized ?: mb_strtolower($original, 'UTF-8'));
        $withoutShort = $this->removeShortWords($baseText);
        if ($withoutShort && $withoutShort !== $baseText) {
            $results = $this->searchBothLanguages($withoutShort);
            $best = $this->findBestMatch($original, $results);
            if ($best) {
                return $this->matchResult([$best], true, $withoutShort);
            }
            $allCandidates = array_merge($allCandidates, $results);
        }

        // ── Katman 5: Typo varyantları (çift harf, kelime kırpma) ──
        foreach ($this->generateTypoVariants($baseText) as $variant) {
            $results = $this->searchBothLanguages($variant);
            $best = $this->findBestMatch($original, $results);
            if ($best) {
                return $this->matchResult([$best], true, $variant);
            }
            $allCandidates = array_merge($allCandidates, $results);
        }

        // ── Katman 6: Kelime parçalama → öneri toplama ──
        $words = explode(' ', $baseText);
        if (count($words) > 1) {
            for ($len = count($words) - 1; $len >= 1; $len--) {
                $partial = implode(' ', array_slice($words, 0, $len));
                if (mb_strlen($partial) < 2) continue;
                $results = $this->searchBothLanguages($partial);
                if (!empty($results)) {
                    $allCandidates = array_merge($allCandidates, $results);
                    break;
                }
            }
        }

        // ── Katman 7: Karakter kırpma (son 1-3 harf) ──
        if (mb_strlen($baseText) > 3) {
            for ($cut = 1; $cut <= 3; $cut++) {
                $shortened = mb_substr($baseText, 0, mb_strlen($baseText) - $cut);
                if (mb_strlen($shortened) < 2) break;
                $results = $this->searchBothLanguages($shortened);
                if (!empty($results)) {
                    $allCandidates = array_merge($allCandidates, $results);
                    break;
                }
            }
        }

        // ── Öneriler: dil filtrele + deduplicate + popülerlik sırala ──
        $suggestions = $this->buildSuggestions($allCandidates);

        return [
            'results'         => [],
            'corrected'       => false,
            'corrected_query' => null,
            'suggestions'     => array_slice($suggestions, 0, 5),
        ];
    }

    /**
     * Eşleşme sonucu döndürme yardımcısı.
     */
    protected function matchResult(array $results, bool $corrected = false, ?string $correctedQuery = null): array
    {
        return [
            'results'         => $results,
            'corrected'       => $corrected,
            'corrected_query' => $correctedQuery,
            'suggestions'     => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  ARAMA + DOĞRULAMA METODLARI
    // ─────────────────────────────────────────────────────────────

    /**
     * Çift dil arama: Önce EN-US, sonra TR-TR ile arama yapar.
     *
     * 📚 NEDEN EN-US ÖNCELİKLİ?
     * TR-TR araması Türkçe çevirisi olmayan filmleri orijinal dilde
     * (Tayca, Korece, vb.) döndürüyor. Örneğin "Girl from Nowhere"
     * TR aramasında "เด็กใหม่" (Tayca) olarak geliyor.
     *
     * EN-US araması ise hemen her film için İngilizce başlık döndürür.
     * Bu yüzden EN sonuçlarını baz alıyoruz, sadece Türkçe orijinal
     * filmler için TR başlığına geçiyoruz (Yeşilçam, Türk dizileri).
     */
    protected function searchBothLanguages(string $query): array
    {
        $enResults = [];

        // 1. EN-US araması → evrensel İngilizce başlıklar
        $response = $this->request('/search/multi', [
            'query'         => $query,
            'include_adult' => false,
            'language'      => 'en-US',
        ]);

        if ($response?->successful()) {
            foreach (($response->json()['results'] ?? []) as $item) {
                $type = $item['media_type'] ?? '';
                if (!in_array($type, ['movie', 'tv'])) continue;

                if ($type === 'tv') {
                    $item['title'] = $item['name'] ?? $item['original_name'] ?? '';
                    $item['release_date'] = $item['first_air_date'] ?? null;
                }
                $item['media_type'] = $type;
                $key = $type . '_' . $item['id'];
                $enResults[$key] = $item;
            }
        }

        // 2. TR-TR araması → Türkçe orijinal filmler için Türkçe başlık
        $response = $this->request('/search/multi', [
            'query'         => $query,
            'include_adult' => false,
            'language'      => 'tr-TR',
        ]);

        if ($response?->successful()) {
            foreach (($response->json()['results'] ?? []) as $item) {
                $type = $item['media_type'] ?? '';
                if (!in_array($type, ['movie', 'tv'])) continue;

                $trTitle = $type === 'tv'
                    ? ($item['name'] ?? $item['original_name'] ?? '')
                    : ($item['title'] ?? $item['original_title'] ?? '');

                if ($type === 'tv') {
                    $item['title'] = $item['name'] ?? $item['original_name'] ?? '';
                    $item['release_date'] = $item['first_air_date'] ?? null;
                }
                $item['media_type'] = $type;
                $key = $type . '_' . $item['id'];

                if (($item['original_language'] ?? '') === 'tr') {
                    // Türkçe orijinal → TR başlığı tercih et
                    $enResults[$key] = $item;
                } elseif (!isset($enResults[$key])) {
                    // EN'de bulunmayan yeni sonuç → ekle
                    $enResults[$key] = $item;
                } else {
                    // Zaten EN'de var → TR başlığını ekle (karşılaştırma için)
                    $enResults[$key]['tr_title'] = $trTitle;
                }
            }
        }

        return array_values($enResults);
    }

    /**
     * Arama sonuçları arasından sorguya en iyi eşleşen sonucu bulur.
     *
     * 📚 NEDEN GEREKLİ?
     * Eski sistem TMDB'nin döndürdüğü ilk sonucu direkt kabul ediyordu.
     * Ama TMDB bazen alakasız sonuçlar döndürüyor:
     *   "Joker" → "Impractical Jokers" (popüler ama yanlış)
     *   "Flipped" → "Flip Flappers" (kelime benzerliği ama yanlış)
     *
     * Bu metod:
     * 1. Her sonucun başlığını sorguyla karşılaştırır (titleSimilarity)
     * 2. Dil filtresini uygular (TR/EN öncelikli)
     * 3. Popülerlik bonusu ekler (tam eşleşmelerde)
     * 4. En yüksek benzerlik skorlu sonucu seçer
     * 5. Skor eşik değerinin altındaysa → null döner (hiçbiri kabul edilmez)
     */
    protected function findBestMatch(string $query, array $results, float $threshold = 0.4): ?array
    {
        if (empty($results)) return null;

        $queryNorm = $this->normalizeForComparison($query);
        $best = null;
        $bestScore = 0;

        foreach ($results as $index => $result) {
            // Her sonucun birden fazla başlık alanı olabilir (EN + TR + orijinal)
            $titles = array_filter([
                $result['title'] ?? '',
                $result['original_title'] ?? $result['original_name'] ?? '',
                $result['name'] ?? '',
                $result['tr_title'] ?? '',  // Türkçe başlık (searchBothLanguages'den)
            ]);

            $maxScore = 0;
            $hasExactMatch = false;
            foreach ($titles as $title) {
                $titleNorm = $this->normalizeForComparison($title);
                $score = $this->titleSimilarity($queryNorm, $titleNorm);
                if ($score >= 0.95) {
                    $hasExactMatch = true;
                }
                $maxScore = max($maxScore, $score);
            }

            // TR/EN filmler için küçük bonus (alakasız dildeki filmleri dezavantajla)
            $lang = $result['original_language'] ?? '';
            if (in_array($lang, ['tr', 'en'])) {
                $maxScore += 0.05;
            }

            // Tam eşleşme varsa, popülerlik ve oy sayısı bonusu ekle
            // Fight Club (pop:23, votes:31687) vs Knuckledust (pop:1.3, votes:21)
            if ($hasExactMatch) {
                $popularity = $result['popularity'] ?? 0;
                $voteCount = $result['vote_count'] ?? 0;
                
                // Popülerlik bonusu (0-0.15 arası)
                // pop 10+ → 0.05, pop 20+ → 0.10, pop 30+ → 0.15
                if ($popularity >= 10) {
                    $maxScore += min(0.15, $popularity / 200);
                }
                
                // Oy sayısı bonusu (0-0.10 arası)
                // 1000+ oy → önemli film, bonus ver
                if ($voteCount >= 1000) {
                    $maxScore += min(0.10, $voteCount / 100000);
                }
            }

            if ($maxScore > $bestScore) {
                $bestScore = $maxScore;
                $best = $result;
            }
        }

        return $bestScore >= $threshold ? $best : null;
    }

    /**
     * İki metin arasındaki başlık benzerliğini hesaplar (0.0 – 1.0).
     *
     * 📚 SKORLAMA SİSTEMİ:
     * - Tam eşleşme: "joker" == "joker" → 1.0
     * - Kelime eşleşme: "korku kapani" vs "korku kapani 3" → yüksek
     * - Kısmi kelime: "joker" vs "jokers" (çoğul) → 0.8
     * - Fazla kelime cezası: Sonuçta ekstra kelime → -0.08/kelime
     *   "joker" vs "impractical jokers" → 0.8 (kelime) - 0.08 (1 extra) = 0.72
     *   Ama: "joker" vs "joker" → 1.0 - 0 = 1.0 → doğru seçilir
     */
    protected function titleSimilarity(string $query, string $title): float
    {
        if ($query === $title) return 1.0;
        if (empty($query) || empty($title)) return 0.0;

        $qWords = array_values(array_filter(explode(' ', $query), fn($w) => mb_strlen($w) >= 1));
        $tWords = array_values(array_filter(explode(' ', $title), fn($w) => mb_strlen($w) >= 1));

        if (empty($qWords)) return 0.0;

        $matchCount = 0;
        foreach ($qWords as $qw) {
            if (mb_strlen($qw) < 1) continue;
            $matched = false;

            foreach ($tWords as $tw) {
                if (mb_strlen($tw) < 1) continue;

                // Tam eşleşme
                if ($qw === $tw) { $matchCount += 1.0; $matched = true; break; }

                // Fuzzy eşleşme: biri diğerinin önekiyse ve uzunluk farkı max %30
                $shorter = mb_strlen($qw) <= mb_strlen($tw) ? $qw : $tw;
                $longer = mb_strlen($qw) > mb_strlen($tw) ? $qw : $tw;
                $lenRatio = mb_strlen($shorter) / mb_strlen($longer);

                if (mb_strlen($shorter) >= 3 && $lenRatio >= 0.7 && str_starts_with($longer, $shorter)) {
                    $matchCount += 0.8;
                    $matched = true;
                    break;
                }

                // Sayılar için tam eşleşme gerekli (3 ≠ 30)
                if (is_numeric($qw) && is_numeric($tw) && $qw === $tw) {
                    $matchCount += 1.0;
                    $matched = true;
                    break;
                }
            }
        }

        // Temel skor: sorgu kelimelerinin kaçı eşleşti
        $baseScore = $matchCount / count($qWords);

        // Fazla kelime cezası: sonuçta ekstra kelime varsa skor düşür
        $extraWords = max(0, count($tWords) - count($qWords));
        $lengthPenalty = $extraWords * 0.08;

        return max(0, $baseScore - $lengthPenalty);
    }

    /**
     * Karşılaştırma için metin normalize eder.
     * normalizeQuery'den farkı: sadece harf + rakam + boşluk kalır, tire bile gider.
     */
    protected function normalizeForComparison(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        // Türkçe → ASCII
        $map = [
            'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u',
            'ö' => 'o', 'ç' => 'c', 'İ' => 'i', 'Ş' => 's',
            'Ğ' => 'g', 'Ü' => 'u', 'Ö' => 'o', 'Ç' => 'c',
            'i̇' => 'i', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
            'à' => 'a', 'â' => 'a', 'ô' => 'o', 'û' => 'u',
            'ñ' => 'n', 'ä' => 'a',
        ];
        $text = str_replace(array_keys($map), array_values($map), $text);

        // Sadece harf, rakam, boşluk
        $text = preg_replace('/[^\p{Latin}\p{N}\s]/u', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Kısa anlamsız kelimeleri (1-2 harf, sayı olmayan) kaldırır.
     *
     * 📚 ÖRNEK:
     * "bridge yo terabithia" → "bridge terabithia" → TMDB bulur!
     * "dhom 3" → "dhom 3" (3 sayı, korunur)
     */
    protected function removeShortWords(string $text): ?string
    {
        $words = explode(' ', $text);
        $filtered = array_filter($words, fn($w) => mb_strlen($w) > 2 || is_numeric($w));

        if (count($filtered) < count($words) && count($filtered) >= 1) {
            return implode(' ', $filtered);
        }
        return null;
    }

    /**
     * Öneri listesi oluşturur: dil filtresi + deduplicate + popülerlik.
     *
     * 📚 DİL FİLTRESİ:
     * Öneriler öncelikle TR/EN filmlerden oluşur.
     * Korece, Japonca, Fransızca, Tayca filmler ancak
     * TR/EN film bulunamazsa gösterilir.
     */
    protected function buildSuggestions(array $candidates): array
    {
        // Deduplicate
        $seen = [];
        $unique = [];
        foreach ($candidates as $item) {
            $key = ($item['media_type'] ?? 'movie') . '_' . ($item['id'] ?? 0);
            if (!in_array($key, $seen)) {
                $seen[] = $key;
                $unique[] = $item;
            }
        }

        // Dil ayrımı: TR/EN öncelikli
        $preferred = [];
        $others = [];
        foreach ($unique as $r) {
            $lang = $r['original_language'] ?? '';
            if (in_array($lang, ['tr', 'en', 'hi'])) {
                $preferred[] = $r;
            } else {
                $others[] = $r;
            }
        }

        // Her grubu popülerliğe göre sırala
        usort($preferred, fn($a, $b) => ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0));
        usort($others, fn($a, $b) => ($b['popularity'] ?? 0) <=> ($a['popularity'] ?? 0));

        // TR/EN önce, sonra diğerleri (max 2 diğer dil)
        return array_merge(array_slice($preferred, 0, 5), array_slice($others, 0, 2));
    }

    /**
     * Yazım hatası varyantları üretir.
     *
     * 📚 YAYGIN TYPO KALIPLARI:
     * 1. Çift harf: "inceptioon" → "inception"
     * 2. Her kelimenin son harfini kırp
     * 3. İlk kelimenin son harfini kırp
     * 4. Kısa kelimeleri çıkar (Katman 4'te de yapılıyor ama burada farklı bağlamda)
     */
    protected function generateTypoVariants(string $text): array
    {
        $variants = [];

        // Tekrarlanan harfleri düzelt: "inceptioon" → "inception"
        $deduped = preg_replace('/(.)\1+/u', '$1', $text);
        if ($deduped !== $text && mb_strlen($deduped) >= 2) {
            $variants[] = $deduped;
        }

        // Her kelimenin son harfini kırp
        $words = explode(' ', $text);
        if (count($words) >= 1) {
            $trimmedWords = array_map(
                fn($w) => mb_strlen($w) > 3 ? mb_substr($w, 0, -1) : $w,
                $words
            );
            $trimmed = implode(' ', $trimmedWords);
            if ($trimmed !== $text && !in_array($trimmed, $variants)) {
                $variants[] = $trimmed;
            }
        }

        // Sadece ilk kelimenin son harfini kırp
        if (count($words) > 1 && mb_strlen($words[0]) > 3) {
            $firstTrimmed = $words;
            $firstTrimmed[0] = mb_substr($words[0], 0, -1);
            $variant = implode(' ', $firstTrimmed);
            if ($variant !== $text && !in_array($variant, $variants)) {
                $variants[] = $variant;
            }
        }

        return array_slice($variants, 0, 5);
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
     * 2.1. Dizi Detayı (yaratıcı bilgisi dahil)
     *
     * 📚 TMDB'de TV dizileri farklı endpoint kullanır:
     * - `/movie/{id}` → Film detayı (title, runtime, credits.crew)
     * - `/tv/{id}` → Dizi detayı (name, episode_run_time, created_by)
     *
     * Dizi detayında `credits` yerine `aggregate_credits` kullanılır,
     * `created_by` alanı ise "yönetmen" yerine geçer.
     */
    public function getTvDetails(int|string $id)
    {
        return $this->request("/tv/{$id}", [
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
