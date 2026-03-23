<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * 📚 CACHE KULLANIMI - İSTATİSTİK SAYFASI
     *
     * İstatistik hesaplamaları yoğun işlemler içerir:
     * - Tüm filmleri çek
     * - Türleri say, grupla
     * - Tarihleri parse et, grupla
     * - Yönetmenleri say
     *
     * Bu işlemler her sayfa yenilemede tekrarlanırsa:
     * - Veritabanı yorulur
     * - Sayfa yavaş açılır
     * - Kullanıcı deneyimi kötüleşir
     *
     * ÇÖZÜM: Cache kullanarak sonuçları 5 dakika saklıyoruz.
     * Film eklenince/silinince cache temizleniyor (bkz: Movie model observer)
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        /**
         * 📚 CACHE KEY STRATEJİSİ
         *
         * Cache key'i kullanıcıya özel yapıyoruz: "user_stats_{user_id}"
         * Böylece her kullanıcının kendi istatistikleri ayrı cache'lenir.
         *
         * Eğer global bir cache olsaydı (örn: "all_stats"):
         * - Kullanıcı A'nın verileri Kullanıcı B'ye görünebilirdi! (Güvenlik açığı)
         */
        $cacheKey = "user_stats_{$user->id}";

        /**
         * 📚 Cache::remember() NASIL ÇALIŞIR?
         *
         * 1. Cache'de $cacheKey var mı diye bakar
         * 2. VARSA: Direkt döndürür (veritabanına hiç gitmez!)
         * 3. YOKSA: Closure'ı çalıştırır, sonucu cache'e yazar, döndürür
         *
         * now()->addMinutes(5) = 5 dakika sonra cache otomatik silinir (TTL)
         */
        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return $this->calculateStatistics($user);
        });

        return view('movies.statistics', $data);
    }

    /**
     * İstatistik hesaplama mantığını ayrı metoda taşıdık.
     *
     * 📚 SINGLE RESPONSIBILITY PRINCIPLE (Tek Sorumluluk İlkesi)
     * - index() metodu: Cache yönetimi ve view döndürme
     * - calculateStatistics(): Sadece hesaplama işlemi
     *
     * Bu ayırım sayesinde:
     * - Kod daha okunabilir
     * - Test yazmak daha kolay
     * - İleride hesaplama mantığını değiştirmek kolay
     */
    private function calculateStatistics(User $user): array
    {
        // Sadece izlenen filmler üzerinden istatistik üretiyoruz
        $watchedMovies = $user->movies()->watched()->get();

        if ($watchedMovies->isEmpty()) {
            return ['hasData' => false];
        }

        // =====================================================================
        // 📚 ÖĞRENME: LARAVEL COLLECTIONS (Koleksiyonlar)
        // Veritabanından gelen veriyi PHP tarafında çok yetenekli metotlarla
        // (map, flatMap, countBy, sum vb.) işlememizi sağlar.
        // =====================================================================

        // 1. Geleneksel İstatistikler (Sayılar)
        $totalWatched = $watchedMovies->count();
        $totalRuntime = $watchedMovies->sum('runtime');
        $totalHours = floor($totalRuntime / 60);
        $remainingMinutes = $totalRuntime % 60;

        $averageRating = $watchedMovies->avg('rating');
        $averagePersonalRating = $watchedMovies->whereNotNull('personal_rating')->avg('personal_rating');

        // 2. Tür Dağılımı (Pie Chart için)
        $genreCounts = $watchedMovies->pluck('genres')
            ->filter()
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(8);

        // 3. İzleme Geçmişi (Aylara Göre Dağılım - Bar Chart)
        $monthlyCounts = $watchedMovies->whereNotNull('watched_at')
            ->groupBy(function ($movie) {
                return $movie->watched_at->format('Y-m');
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->sortKeys();

        // 4. En Çok Film İzlenen Yönetmenler
        $directorCounts = $watchedMovies->pluck('director')
            ->filter(fn($d) => !empty($d) && $d !== 'Bilinmiyor')
            ->countBy()
            ->sortDesc()
            ->take(5);

        // 5. Yıllara Göre Film Dağılımı (Release Date)
        $releaseYearCounts = $watchedMovies->pluck('release_date')
            ->filter()
            ->map(function ($date) {
                return substr($date, 0, 4);
            })
            ->countBy()
            ->sortKeysDesc()
            ->take(10);

        return [
            'hasData' => true,
            'stats' => compact('totalWatched', 'totalHours', 'remainingMinutes', 'averageRating', 'averagePersonalRating'),
            'chartData' => [
                'genres' => [
                    'labels' => $genreCounts->keys()->toArray(),
                    'data' => $genreCounts->values()->toArray(),
                ],
                'monthly' => [
                    'labels' => $monthlyCounts->keys()->toArray(),
                    'data' => $monthlyCounts->values()->toArray(),
                ],
                'directors' => [
                    'labels' => $directorCounts->keys()->toArray(),
                    'data' => $directorCounts->values()->toArray(),
                ],
                'years' => [
                    'labels' => $releaseYearCounts->keys()->toArray(),
                    'data' => $releaseYearCounts->values()->toArray(),
                ]
            ]
        ];
    }
}
