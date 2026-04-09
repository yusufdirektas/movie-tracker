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

        // Referans kullanıcıyı belirle (az film olan)
        $reference = $this->determineReferenceUser($userA, $userB, $moviesA, $moviesB);
        $refMovies = $reference['user']->id === $userA->id ? $moviesA : $moviesB;
        $otherMovies = $reference['user']->id === $userA->id ? $moviesB : $moviesA;

        // Ortak filmleri bul
        $commonTmdbIds = $moviesA->pluck('tmdb_id')->intersect($moviesB->pluck('tmdb_id'));
        $commonMoviesA = $moviesA->whereIn('tmdb_id', $commonTmdbIds);
        $commonMoviesB = $moviesB->whereIn('tmdb_id', $commonTmdbIds);

        // Her boyutu ayrı ayrı hesapla
        $movieAnalysis    = $this->analyzeMovies($moviesA, $moviesB);
        $genreAnalysis    = $this->analyzeGenres($commonMoviesA, $commonMoviesB);
        $directorAnalysis = $this->analyzeDirectors($refMovies, $otherMovies);
        $castAnalysis     = $this->analyzeCast($refMovies, $otherMovies);
        $decadeAnalysis   = $this->analyzeDecades($refMovies, $otherMovies);
        $ratingAnalysis   = $this->analyzeRatings($moviesA, $moviesB);

        /**
         * 📚 AĞIRLIKLI ORTALAMA (Weighted Average)
         *
         * Her boyutun score'u × ağırlığı = toplam skor
         */
        $overallScore = round(
            ($movieAnalysis['score']    * self::WEIGHTS['movies']) +
            ($genreAnalysis['score']    * self::WEIGHTS['genres']) +
            ($directorAnalysis['score'] * self::WEIGHTS['directors']) +
            ($castAnalysis['score']     * self::WEIGHTS['cast']) +
            ($decadeAnalysis['score']   * self::WEIGHTS['decades']) +
            ($ratingAnalysis['score']   * self::WEIGHTS['ratings'])
        );

        // Güven skorunu hesapla
        $confidence = $this->calculateConfidence(
            $movieAnalysis['common_count'],
            min($moviesA->count(), $moviesB->count()),
            $movieAnalysis['score']
        );

        return [
            'overall_score' => $overallScore,
            'confidence'    => $confidence,
            'reference_user_id'   => $reference['user']->id,
            'reference_user_name' => $reference['user']->name,
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

    /**
     * 📚 REFERANS KULLANICI BELİRLEME
     *
     * Az film izleyen kullanıcıyı "referans" olarak belirle.
     * Tüm analizler bu kullanıcının perspektifinden yapılır.
     *
     * Neden? 500 vs 200 film durumunda, 200 filmi olan kişinin
     * zevklerinin ne kadar karşılandığını ölçmek daha anlamlı.
     */
    private function determineReferenceUser(User $userA, User $userB, Collection $moviesA, Collection $moviesB): array
    {
        $countA = $moviesA->count();
        $countB = $moviesB->count();

        if ($countA <= $countB) {
            return ['user' => $userA, 'count' => $countA];
        }

        return ['user' => $userB, 'count' => $countB];
    }

    /**
     * 📚 GÜVEN SKORU HESAPLAMA
     *
     * Analiz sonucunun ne kadar güvenilir olduğunu belirler.
     *
     * Faktörler:
     * - Ortak film sayısı (10 film = düşük, 50+ = yüksek)
     * - Örtüşme oranı
     *
     * @return array ['score' => 0-100, 'level' => 'low'|'medium'|'high', 'label' => string]
     */
    private function calculateConfidence(int $commonCount, int $minMovieCount, int $overlapScore): array
    {
        // Ortak film faktörü: 20 ortak film = %50 katkı, 40+ = %100 katkı
        $commonFactor = min(100, ($commonCount / 20) * 50);

        // Örtüşme faktörü: Overlap score'un yarısı kadar katkı
        $overlapFactor = $overlapScore / 2;

        $score = (int) min(100, $commonFactor + $overlapFactor);

        // Seviye belirleme
        if ($score <= 30) {
            return [
                'score' => $score,
                'level' => 'low',
                'label' => 'Az veri, tahmini sonuç'
            ];
        } elseif ($score <= 60) {
            return [
                'score' => $score,
                'level' => 'medium',
                'label' => 'Makul güvenilirlik'
            ];
        }

        return [
            'score' => $score,
            'level' => 'high',
            'label' => 'Güvenilir sonuç'
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
     * 2. TÜR UYUMU ANALİZİ (Sadece Ortak Filmler)
     *
     * 📚 YENİ YAKLAŞIM: SADECE ORTAK FİLMLER
     *
     * Sadece iki kullanıcının da izlediği filmlerin türlerini karşılaştır.
     * Bu şekilde gerçek zevk uyumu ölçülür.
     *
     * Soru: "Ortak izlediğimiz filmlerde hangi türler öne çıkıyor?"
     */
    private function analyzeGenres(Collection $commonMoviesA, Collection $commonMoviesB): array
    {
        // Ortak filmlerin türlerini topla (her iki kullanıcının versiyonundan)
        $genresA = $this->buildDistribution($commonMoviesA, 'genres');
        $genresB = $this->buildDistribution($commonMoviesB, 'genres');

        // Ortak türler ve sayıları
        $commonGenres = array_intersect_key($genresA, $genresB);

        // Ortak tür sayıları (min ile)
        $topCommon = [];
        foreach ($commonGenres as $genre => $countA) {
            $topCommon[$genre] = min($countA, $genresB[$genre]);
        }
        arsort($topCommon);
        
        // Top common enriched
        $enrichedCommonGenres = [];
        $slicedCommon = array_slice($topCommon, 0, 18, true);

        foreach ($slicedCommon as $genre => $count) {
            $enrichedCommonGenres[] = [
                'name' => $genre,
                'count' => $count
            ];
        }

        // Cosine similarity - ortak filmlerin tür dağılımı
        $score = round($this->cosineSimilarity($genresA, $genresB) * 100);
        $genreMovies = $this->buildGenreMovieMap($commonMoviesA);

        return [
            'score'        => $score,
            'common_film_count' => $commonMoviesA->count(),
            'common'       => $topCommon,
            'top_common'   => $enrichedCommonGenres,
            'genre_movies' => $genreMovies,
        ];
    }

    /**
     * 3. YÖNETMEN UYUMU ANALİZİ (Referans Bazlı Overlap)
     *
     * 📚 REFERANS YAKLAŞIMI
     *
     * refMovies = referans kullanıcının filmleri (az film olan)
     * otherMovies = diğer kullanıcının filmleri
     *
     * Soru: "Referansın izlediği yönetmenleri diğeri ne kadar izlemiş?"
     */
    private function analyzeDirectors(Collection $refMovies, Collection $otherMovies): array
    {
        $directorsRef = $this->buildDistribution($refMovies, 'director');
        $directorsOther = $this->buildDistribution($otherMovies, 'director');

        // 'Bilinmiyor' yönetmeni hariç tut
        unset($directorsRef['Bilinmiyor'], $directorsOther['Bilinmiyor']);

        $commonDirectors = array_intersect_key($directorsRef, $directorsOther);

        // Referans kullanıcının yönetmenlerinin yüzde kaçı diğerinde var?
        $refCount = count($directorsRef);
        $score = $refCount > 0
            ? round((count($commonDirectors) / $refCount) * 100)
            : 0;

        // En çok birlikte izlenen yönetmenler - sadece ortak film sayısı
        $topCommon = [];
        foreach ($commonDirectors as $director => $countRef) {
            // min() ile ortak izlenen film sayısını al
            $topCommon[$director] = min($countRef, $directorsOther[$director]);
        }
        arsort($topCommon);

        $topDirectorsSlice = array_slice($topCommon, 0, 15, true);
        $enrichedTopDirectors = [];

        if (!empty($topDirectorsSlice)) {
            $tmdbService = app(\App\Services\TmdbService::class);
            foreach ($topDirectorsSlice as $director => $commonFilmCount) {
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
                    'common_films' => $commonFilmCount,
                    'profile_path' => $profilePath
                ];
            }
        }

        return [
            'score'         => min($score, 100),
            'common_count'  => count($commonDirectors),
            'ref_unique'    => count($directorsRef),
            'other_unique'  => count($directorsOther),
            'top_common'    => $enrichedTopDirectors,
            'ref_top'       => array_slice($directorsRef, 0, 5, true),
            'other_top'     => array_slice($directorsOther, 0, 5, true),
        ];
    }

    /**
     * 4. OYUNCU UYUMU ANALİZİ (Referans Bazlı Overlap)
     *
     * 📚 REFERANS YAKLAŞIMI
     *
     * Soru: "Referansın izlediği oyuncuları diğeri ne kadar izlemiş?"
     */
    private function analyzeCast(Collection $refMovies, Collection $otherMovies): array
    {
        $castRef = $this->buildDistribution($refMovies, 'cast');
        $castOther = $this->buildDistribution($otherMovies, 'cast');

        $commonCast = array_intersect_key($castRef, $castOther);

        // Referans kullanıcının oyuncularının yüzde kaçı diğerinde var?
        $refCount = count($castRef);
        $score = $refCount > 0
            ? round((count($commonCast) / $refCount) * 100)
            : 0;

        // En çok birlikte izlenen oyuncular - sadece ortak film sayısı
        $topCommon = [];
        foreach ($commonCast as $actor => $countRef) {
            // min() ile ortak izlenen film sayısını al
            $topCommon[$actor] = min($countRef, $castOther[$actor]);
        }
        arsort($topCommon);

        $topActorsSlice = array_slice($topCommon, 0, 15, true);
        $enrichedTopActors = [];

        if (!empty($topActorsSlice)) {
            $tmdbService = app(\App\Services\TmdbService::class);
            foreach ($topActorsSlice as $actor => $commonFilmCount) {
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
                    'common_films' => $commonFilmCount,
                    'profile_path' => $profilePath
                ];
            }
        }

        return [
            'score'         => min($score, 100),
            'common_count'  => count($commonCast),
            'ref_unique'    => count($castRef),
            'other_unique'  => count($castOther),
            'top_common'    => $enrichedTopActors,
            'ref_top'       => array_slice($castRef, 0, 5, true),
            'other_top'     => array_slice($castOther, 0, 5, true),
        ];
    }

    /**
     * 5. DÖNEM UYUMU ANALİZİ (Referans Bazlı Cosine Similarity)
     *
     * 📚 REFERANS YAKLAŞIMI
     *
     * refMovies = referans kullanıcının filmleri
     * Dönem dağılımları cosine similarity ile karşılaştırılır.
     */
    private function analyzeDecades(Collection $refMovies, Collection $otherMovies): array
    {
        $decadesRef = $this->buildDecadeDistribution($refMovies);
        $decadesOther = $this->buildDecadeDistribution($otherMovies);

        $commonDecades = array_intersect_key($decadesRef, $decadesOther);

        $score = round($this->cosineSimilarity($decadesRef, $decadesOther) * 100);

        return [
            'score'         => $score,
            'ref_decades'   => $decadesRef,
            'other_decades' => $decadesOther,
            'common'        => $commonDecades,
        ];
    }

    /**
     * 6. PUAN EĞİLİMİ ANALİZİ (Ortak Filmler Bazlı)
     *
     * 📚 YENİ YAKLAŞIM: SADECE ORTAK FİLMLER
     *
     * Eski problem: A'nın 500 filminin ortalaması vs B'nin 100 filminin ortalaması
     * → Farklı filmler izlemişler, karşılaştırma anlamsız!
     *
     * Yeni yaklaşım: Sadece ikisinin de izlediği filmlerdeki puanları karşılaştır.
     * → "Aynı filmlere benzer puanlar mı verdiler?"
     *
     * Korelasyon: Puanlar arasındaki ilişkiyi ölçer.
     * +1 = Mükemmel uyum (aynı filmlere aynı puanlar)
     *  0 = İlişki yok
     * -1 = Ters uyum (birinin sevdiğini diğeri sevmiyor)
     */
    private function analyzeRatings(Collection $moviesA, Collection $moviesB): array
    {
        // Ortak filmleri bul (tmdb_id bazlı)
        $idsA = $moviesA->pluck('tmdb_id')->toArray();
        $idsB = $moviesB->pluck('tmdb_id')->toArray();
        $commonIds = array_intersect($idsA, $idsB);

        // Ortak filmlerde puan verilerini eşleştir
        $ratingsA = [];
        $ratingsB = [];

        foreach ($commonIds as $tmdbId) {
            $movieA = $moviesA->firstWhere('tmdb_id', $tmdbId);
            $movieB = $moviesB->firstWhere('tmdb_id', $tmdbId);

            // Her ikisinin de puan verdiği filmler
            $ratingA = $movieA->personal_rating ?? $movieA->rating ?? null;
            $ratingB = $movieB->personal_rating ?? $movieB->rating ?? null;

            if ($ratingA !== null && $ratingB !== null && $ratingA > 0 && $ratingB > 0) {
                $ratingsA[] = $ratingA;
                $ratingsB[] = $ratingB;
            }
        }

        // Yeterli veri yoksa
        if (count($ratingsA) < 3) {
            return [
                'score'           => 0,
                'common_rated'    => count($ratingsA),
                'my_avg'          => 0,
                'their_avg'       => 0,
                'correlation'     => 0,
                'difference'      => 0,
                'insufficient'    => true,
                'insufficient_reason' => 'En az 3 ortak puanlanmış film gerekli'
            ];
        }

        // Ortalamalar
        $avgA = array_sum($ratingsA) / count($ratingsA);
        $avgB = array_sum($ratingsB) / count($ratingsB);

        // Pearson korelasyonu hesapla
        $correlation = $this->pearsonCorrelation($ratingsA, $ratingsB);

        // Korelasyonu 0-100 skora çevir
        // -1 → 0, 0 → 50, +1 → 100
        $score = (int) round(($correlation + 1) * 50);

        // Ortalama puan farkı (bilgilendirme amaçlı)
        $diff = abs($avgA - $avgB);

        return [
            'score'           => max(0, min(100, $score)),
            'common_rated'    => count($ratingsA),
            'my_avg'          => round($avgA, 1),
            'their_avg'       => round($avgB, 1),
            'correlation'     => round($correlation, 2),
            'difference'      => round($diff, 1),
            'insufficient'    => false,
        ];
    }

    /**
     * 📚 PEARSON KORELASYON KATSAYISI
     *
     * İki dizi arasındaki doğrusal ilişkiyi ölçer (-1 ile +1 arası).
     * Film puanları için ideal: "Benzer zevkler mi?"
     */
    private function pearsonCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n === 0) {
            return 0.0;
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));

        if ($denominator == 0) {
            return 0.0;
        }

        return $numerator / $denominator;
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
     * Ortak filmleri tür bazında grupla.
     *
     * @return array<string, array<int, array{id:int,title:string,poster_path:?string,release_year:?string}>>
     */
    private function buildGenreMovieMap(Collection $movies): array
    {
        $genreMovies = [];

        foreach ($movies as $movie) {
            if (empty($movie->genres) || !is_array($movie->genres)) {
                continue;
            }

            $moviePayload = [
                'id' => $movie->id,
                'title' => $movie->title,
                'poster_path' => $movie->poster_path,
                'release_year' => $movie->release_date?->format('Y'),
            ];

            foreach ($movie->genres as $genre) {
                if (!$genre) {
                    continue;
                }

                $genreMovies[$genre][] = $moviePayload;
            }
        }

        return $genreMovies;
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
