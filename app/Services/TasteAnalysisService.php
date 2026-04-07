<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 📚 TASTE ANALYSIS SERVICE (Film Zevk Analizi Servisi)
 *
 * ─────────────────────────────────────────────────────────────
 * AMAÇ: İki kullanıcının film zevklerini çok boyutlu analiz etmek.
 * ─────────────────────────────────────────────────────────────
 *
 * 📚 DENGE PROBLEMİ VE ÇÖZÜMÜ
 *
 * Eski yaklaşım (Jaccard): |A ∩ B| / |A ∪ B|
 *   Problem: 700 film vs 100 film → ortak 80 film
 *   Jaccard = 80 / 720 = %11 → Haksız derecede düşük!
 *
 * Yeni yaklaşım (Overlap Coefficient): |A ∩ B| / min(|A|, |B|)
 *   Aynı senaryo: 80 / 100 = %80 → Küçük listenin %80'i ortaklaşa!
 *
 * Neden Overlap Coefficient?
 * → "Daha az film izlemiş kişinin listesinin ne kadarı örtüşüyor?"
 * → Film sayısı farkını cezalandırmaz, zevk ortaklığına odaklanır.
 *
 * Kullanılan algoritmalar:
 * 1. Ortak Filmler (Overlap Coefficient)   → %25 ağırlık
 * 2. Tür Uyumu (Cosine Similarity)         → %25 ağırlık
 * 3. Yönetmen Uyumu (Overlap Coefficient)  → %15 ağırlık
 * 4. Oyuncu Uyumu (Overlap Coefficient)    → %15 ağırlık
 * 5. Dönem Uyumu (Cosine Similarity)       → %10 ağırlık
 * 6. Puan Eğilimi Uyumu                    → %10 ağırlık
 */
class TasteAnalysisService
{
    /**
     * 📚 AĞIRLIK KATSAYILARİ
     *
     * Her boyutun toplam skora katkısını belirler.
     * Toplamları 1.0 (100%) olmalı.
     */
    private const WEIGHTS = [
        'movies'    => 0.25, // Ortak film benzerliği
        'genres'    => 0.25, // Tür dağılımı uyumu
        'directors' => 0.15, // Yönetmen tercihi uyumu
        'cast'      => 0.15, // Oyuncu tercihi uyumu
        'decades'   => 0.10, // Dönem tercihi uyumu
        'ratings'   => 0.10, // Puan eğilimi uyumu
    ];

    /**
     * İki kullanıcının tüm boyutlarda uyum analizini yap.
     *
     * @return array Tüm analiz verileri
     */
    public function analyze(User $userA, User $userB): array
    {
        // Her iki kullanıcının izlediği filmleri çek (tek sorguda tüm veriler)
        $moviesA = $userA->movies()
            ->where('is_watched', true)
            ->whereNotNull('tmdb_id')
            ->get();

        $moviesB = $userB->movies()
            ->where('is_watched', true)
            ->whereNotNull('tmdb_id')
            ->get();

        // Her boyutu ayrı ayrı hesapla
        $movieAnalysis    = $this->analyzeMovies($moviesA, $moviesB);
        $genreAnalysis    = $this->analyzeGenres($moviesA, $moviesB);
        $directorAnalysis = $this->analyzeDirectors($moviesA, $moviesB);
        $castAnalysis     = $this->analyzeCast($moviesA, $moviesB);
        $decadeAnalysis   = $this->analyzeDecades($moviesA, $moviesB);
        $ratingAnalysis   = $this->analyzeRatings($moviesA, $moviesB);

        /**
         * 📚 AĞIRLIKLI ORTALAMA (Weighted Average)
         *
         * Her boyutun score'u × ağırlığı = toplam skor
         *
         * Örnek:
         *   Film: 0.50 × 0.25 = 0.125
         *   Tür:  0.80 × 0.25 = 0.200
         *   ...
         *   Toplam = 0.125 + 0.200 + ... = 0.XX → %XX
         */
        $overallScore = round(
            ($movieAnalysis['score']    * self::WEIGHTS['movies']) +
            ($genreAnalysis['score']    * self::WEIGHTS['genres']) +
            ($directorAnalysis['score'] * self::WEIGHTS['directors']) +
            ($castAnalysis['score']     * self::WEIGHTS['cast']) +
            ($decadeAnalysis['score']   * self::WEIGHTS['decades']) +
            ($ratingAnalysis['score']   * self::WEIGHTS['ratings'])
        );

        return [
            'overall_score' => $overallScore,
            'weights'       => self::WEIGHTS,
            'dimensions'    => [
                'movies'    => $movieAnalysis,
                'genres'    => $genreAnalysis,
                'directors' => $directorAnalysis,
                'cast'      => $castAnalysis,
                'decades'   => $decadeAnalysis,
                'ratings'   => $ratingAnalysis,
            ],
            'my_total'    => $moviesA->count(),
            'their_total' => $moviesB->count(),
        ];
    }

    // =========================================================================
    // 📚 BOYUT ANALİZLERİ
    // =========================================================================

    /**
     * 1. ORTAK FİLM ANALİZİ (Overlap Coefficient)
     *
     * 📚 OVERLAP COEFFICIENT (Örtüşme Katsayısı)
     *
     * Formül: |A ∩ B| / min(|A|, |B|)
     *
     * Neden Jaccard yerine bu?
     *   Jaccard: 80 ortak / 720 birleşim = %11 (haksız!)
     *   Overlap: 80 ortak / 100 (küçük set) = %80 (adil!)
     *
     * "Küçük listenin ne kadarı büyük listede var?"
     * Film sayısı farkını cezalandırmaz.
     */
    private function analyzeMovies(Collection $moviesA, Collection $moviesB): array
    {
        $idsA = $moviesA->pluck('tmdb_id');
        $idsB = $moviesB->pluck('tmdb_id');

        $commonIds   = $idsA->intersect($idsB)->values();
        $onlyAIds    = $idsA->diff($idsB)->values();
        $onlyBIds    = $idsB->diff($idsA)->values();

        // Overlap Coefficient → min(|A|, |B|) tabanlı
        $minCount = min($idsA->count(), $idsB->count());
        $score = $minCount > 0
            ? round(($commonIds->count() / $minCount) * 100)
            : 0;

        return [
            'score'            => min($score, 100), // %100'ü geçmesin
            'common_count'     => $commonIds->count(),
            'only_a_count'     => $onlyAIds->count(),
            'only_b_count'     => $onlyBIds->count(),
            'common_tmdb_ids'  => $commonIds,
            'only_a_tmdb_ids'  => $onlyAIds,
            'only_b_tmdb_ids'  => $onlyBIds,
        ];
    }

    /**
     * 2. TÜR UYUMU ANALİZİ (Cosine Similarity)
     *
     * 📚 COSINE SIMILARITY NEDİR?
     *
     * İki vektörün açısını ölçer. Vektörlerin büyüklüğü önemsiz,
     * yönleri (oranları) önemli.
     *
     * Kullanıcı A: Drama %40, Aksiyon %30, Komedi %20, Korku %10
     * Kullanıcı B: Drama %35, Aksiyon %35, Komedi %20, Korku %10
     * → Çok benzer dağılım = yüksek cosine similarity
     *
     * Cosine zaten film sayısı farkından etkilenmez (oranlarla çalışır).
     */
    private function analyzeGenres(Collection $moviesA, Collection $moviesB): array
    {
        $genresA = $this->buildDistribution($moviesA, 'genres');
        $genresB = $this->buildDistribution($moviesB, 'genres');

        // Ortak türler (ikisi de izlemiş)
        $commonGenres = array_intersect_key($genresA, $genresB);

        // En çok ortaklaşa izlenen türler (sıralı)
        $topCommon = [];
        foreach ($commonGenres as $genre => $countA) {
            $topCommon[$genre] = min($countA, $genresB[$genre]);
        }
        arsort($topCommon);
        // Top common enriched
        $enrichedCommonGenres = [];
        $slicedCommon = array_slice($topCommon, 0, 18, true);
        
        foreach ($slicedCommon as $genre => $countA) {
            $enrichedCommonGenres[] = [
                'name' => $genre,
                'count' => min($countA, $genresB[$genre])
            ];
        }

        $score = round($this->cosineSimilarity($genresA, $genresB) * 100);

        return [
            'score'       => $score,
            'my_genres'   => $genresA,
            'their_genres' => $genresB,
            'common'      => $topCommon,
            'top_common'  => $enrichedCommonGenres,
        ];
    }

    /**
     * 3. YÖNETMEN UYUMU ANALİZİ (Overlap Coefficient)
     *
     * Overlap Coefficient kullanıyoruz çünkü 700 film izleyen kişi
     * doğal olarak çok daha fazla yönetmen tanıyacak.
     * min() ile küçük seti baz alıyoruz → adil karşılaştırma.
     */
    private function analyzeDirectors(Collection $moviesA, Collection $moviesB): array
    {
        $directorsA = $this->buildDistribution($moviesA, 'director');
        $directorsB = $this->buildDistribution($moviesB, 'director');

        // 'Bilinmiyor' yönetmeni hariç tut
        unset($directorsA['Bilinmiyor'], $directorsB['Bilinmiyor']);

        $commonDirectors = array_intersect_key($directorsA, $directorsB);

        // Overlap Coefficient
        $minUniqueCount = min(count($directorsA), count($directorsB));
        $score = $minUniqueCount > 0
            ? round((count($commonDirectors) / $minUniqueCount) * 100)
            : 0;

        // En çok birlikte izlenen yönetmenler
        $topCommon = [];
        foreach ($commonDirectors as $director => $countA) {
            $topCommon[$director] = $countA + $directorsB[$director];
        }
        arsort($topCommon);
        
        $topDirectorsSlice = array_slice($topCommon, 0, 15, true);
        $enrichedTopDirectors = [];
        
        if (!empty($topDirectorsSlice)) {
            $tmdbService = app(\App\Services\TmdbService::class);
            foreach ($topDirectorsSlice as $director => $totalFilms) {
                // Same caching and search logic used for actors
                $cacheKey = 'director_image_' . md5($director);
                $profilePath = \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function() use ($tmdbService, $director) {
                    $response = $tmdbService->searchPerson($director);
                    if ($response && $response->successful()) {
                        $results = $response->json('results');
                        if (!empty($results) && !empty($results[0]['profile_path'])) {
                            return $results[0]['profile_path'];
                        }
                    }
                    return null;
                });
                
                $enrichedTopDirectors[] = [
                    'name' => $director,
                    'total_films' => $totalFilms,
                    'profile_path' => $profilePath
                ];
            }
        }

        return [
            'score'         => min($score, 100),
            'common_count'  => count($commonDirectors),
            'my_unique'     => count($directorsA),
            'their_unique'  => count($directorsB),
            'top_common'    => $enrichedTopDirectors,
            'my_top'        => array_slice($directorsA, 0, 5, true),
            'their_top'     => array_slice($directorsB, 0, 5, true),
        ];
    }

    /**
     * 4. OYUNCU UYUMU ANALİZİ (Overlap Coefficient)
     */
    private function analyzeCast(Collection $moviesA, Collection $moviesB): array
    {
        $castA = $this->buildDistribution($moviesA, 'cast');
        $castB = $this->buildDistribution($moviesB, 'cast');

        $commonCast = array_intersect_key($castA, $castB);

        // Overlap Coefficient
        $minUnique = min(count($castA), count($castB));
        $score = $minUnique > 0
            ? round((count($commonCast) / $minUnique) * 100)
            : 0;

        // En çok birlikte izlenen oyuncular
        $topCommon = [];
        foreach ($commonCast as $actor => $countA) {
            $topCommon[$actor] = $countA + $castB[$actor];
        }
        arsort($topCommon);
        
        $topActorsSlice = array_slice($topCommon, 0, 15, true);
        $enrichedTopActors = [];
        
        if (!empty($topActorsSlice)) {
            $tmdbService = app(\App\Services\TmdbService::class);
            foreach ($topActorsSlice as $actor => $totalFilms) {
                $cacheKey = 'actor_image_' . md5($actor);
                $profilePath = \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function() use ($tmdbService, $actor) {
                    $response = $tmdbService->searchPerson($actor);
                    if ($response && $response->successful()) {
                        $results = $response->json('results');
                        if (!empty($results) && !empty($results[0]['profile_path'])) {
                            return $results[0]['profile_path'];
                        }
                    }
                    return null;
                });
                
                $enrichedTopActors[] = [
                    'name' => $actor,
                    'total_films' => $totalFilms,
                    'profile_path' => $profilePath
                ];
            }
        }

        return [
            'score'         => min($score, 100),
            'common_count'  => count($commonCast),
            'my_unique'     => count($castA),
            'their_unique'  => count($castB),
            'top_common'    => $enrichedTopActors,
            'my_top'        => array_slice($castA, 0, 5, true),
            'their_top'     => array_slice($castB, 0, 5, true),
        ];
    }

    /**
     * 5. DÖNEM UYUMU ANALİZİ (Cosine Similarity)
     *
     * Filmlerin çıkış yıllarını 10'ar yıllık dönemlere ayırıp
     * iki kullanıcının dönem tercihlerini karşılaştırır.
     *
     * Cosine zaten oran bazlı çalıştığı için film sayısı farkından
     * etkilenmez. 700 vs 100 film olsa bile dağılım benzerliğini ölçer.
     */
    private function analyzeDecades(Collection $moviesA, Collection $moviesB): array
    {
        $decadesA = $this->buildDecadeDistribution($moviesA);
        $decadesB = $this->buildDecadeDistribution($moviesB);

        $commonDecades = array_intersect_key($decadesA, $decadesB);

        $score = round($this->cosineSimilarity($decadesA, $decadesB) * 100);

        return [
            'score'       => $score,
            'my_decades'  => $decadesA,
            'their_decades' => $decadesB,
            'common'      => $commonDecades,
        ];
    }

    /**
     * 6. PUAN EĞİLİMİ ANALİZİ
     *
     * 📚 EDGE CASE: Bir tarafın hiç filmi yoksa
     *
     * Eski problem: A ortalaması 6.8, B ortalaması 0.0 (film yok)
     * → Fark = 6.8 → Score = %32 → Bu yanıltıcı!
     *
     * Çözüm: İki tarafın da en az 1 filmi yoksa skor 0 dönder.
     * Böylece "veri yetersiz" durumunda sahte uyum gösterilmez.
     */
    private function analyzeRatings(Collection $moviesA, Collection $moviesB): array
    {
        $ratedA = $moviesA->whereNotNull('rating')->where('rating', '>', 0);
        $ratedB = $moviesB->whereNotNull('rating')->where('rating', '>', 0);

        $avgA = $ratedA->count() > 0 ? $ratedA->avg('rating') : 0;
        $avgB = $ratedB->count() > 0 ? $ratedB->avg('rating') : 0;

        $personalAvgA = $moviesA->whereNotNull('personal_rating')->avg('personal_rating') ?? 0;
        $personalAvgB = $moviesB->whereNotNull('personal_rating')->avg('personal_rating') ?? 0;

        // İki tarafın da puanlı filmi olmalı, yoksa karşılaştırma anlamsız
        if ($ratedA->count() === 0 || $ratedB->count() === 0) {
            return [
                'score'          => 0,
                'my_avg'         => round($avgA, 1),
                'their_avg'      => round($avgB, 1),
                'my_personal'    => round($personalAvgA, 1),
                'their_personal' => round($personalAvgB, 1),
                'difference'     => 0,
                'insufficient'   => true, // UI bunu kontrol edecek
            ];
        }

        // Puan farkı ne kadar küçükse uyum o kadar yüksek
        $diff  = abs($avgA - $avgB);
        $score = round((1 - ($diff / 10)) * 100);

        return [
            'score'          => max(0, $score),
            'my_avg'         => round($avgA, 1),
            'their_avg'      => round($avgB, 1),
            'my_personal'    => round($personalAvgA, 1),
            'their_personal' => round($personalAvgB, 1),
            'difference'     => round($diff, 1),
            'insufficient'   => false,
        ];
    }

    // =========================================================================
    // 📚 YARDIMCI METODLAR (Helper Methods)
    // =========================================================================

    /**
     * Film koleksiyonundan bir alanın frekans dağılımını oluştur.
     *
     * 📚 BU METOD NE YAPAR?
     *
     * Girdi: Film listesi + alan adı ('genres', 'director', 'cast')
     * Çıktı: ['Aksiyon' => 15, 'Dram' => 12, 'Komedi' => 8, ...]
     *
     * genres ve cast alanları JSON array (["Aksiyon","Dram"]) olduğu için
     * flatten() ile düzleştiriyoruz. director ise tekil string.
     *
     * countBy() → Her benzersiz değerin kaç kez geçtiğini sayar.
     * sortDesc() → En çok geçenden en aza sıralar.
     */
    private function buildDistribution(Collection $movies, string $field): array
    {
        if ($field === 'director') {
            return $movies
                ->pluck($field)
                ->filter()
                ->countBy()
                ->sortDesc()
                ->toArray();
        }

        return $movies
            ->pluck($field)
            ->filter()
            ->flatten()
            ->filter()
            ->countBy()
            ->sortDesc()
            ->toArray();
    }

    /**
     * Film koleksiyonundan dönem (decade) dağılımı oluştur.
     *
     * release_date: "2019-04-26" → yıl: 2019 → decade: 2010
     * Formül: floor(yıl / 10) * 10
     */
    private function buildDecadeDistribution(Collection $movies): array
    {
        return $movies
            ->filter(fn($m) => $m->release_date)
            ->map(function ($movie) {
                $year = (int) $movie->release_date->format('Y');
                return (string) (floor($year / 10) * 10) . "'ler";
            })
            ->countBy()
            ->sortKeys()
            ->toArray();
    }

    /**
     * 📚 COSİNE SİMİLARİTY HESAPLAMASI
     *
     * İki frekans dağılımının benzerliğini ölçer.
     * Film sayısı farkından etkilenmez (oranlarla çalışır).
     */
    private function cosineSimilarity(array $distA, array $distB): float
    {
        if (empty($distA) || empty($distB)) {
            return 0.0;
        }

        $allKeys = array_unique(array_merge(array_keys($distA), array_keys($distB)));

        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        foreach ($allKeys as $key) {
            $valA = $distA[$key] ?? 0;
            $valB = $distB[$key] ?? 0;

            $dotProduct += $valA * $valB;
            $magnitudeA += $valA * $valA;
            $magnitudeB += $valB * $valB;
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0.0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }
}
