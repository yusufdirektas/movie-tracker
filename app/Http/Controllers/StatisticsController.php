<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatisticsController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Sadece izlenen filmler üzerinden istatistik üretiyoruz
        $watchedMovies = $user->movies()->watched()->get();

        if ($watchedMovies->isEmpty()) {
            return view('movies.statistics', ['hasData' => false]);
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
        // Her filmin 'genres' array'ini alır, düzleştirir (flatten) ve kaçar tane geçtiğini sayar (countBy).
        $genreCounts = $watchedMovies->pluck('genres')
            ->filter() // null olanları çıkar
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(8); // Sadece en popüler 8 türü grafikte göster

        // 3. İzleme Geçmişi (Aylara Göre Dağılım - Bar Chart)
        // 'watched_at' tarihini 'Yıl-Ay' (örn 2024-03) formatına çevirip sayıyoruz.
        $monthlyCounts = $watchedMovies->whereNotNull('watched_at')
            ->groupBy(function ($movie) {
                return $movie->watched_at->format('Y-m'); // "2024-03" -> [Movie, Movie, ...]
            })
            ->map(function ($group) {
                return $group->count(); // "2024-03" -> 2
            })
            ->sortKeys(); // Tarih sırasına diz

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
                return substr($date, 0, 4); // "2023-05-12" -> "2023"
            })
            ->countBy()
            ->sortKeysDesc()
            ->take(10); // Son 10 yılı göster

        return view('movies.statistics', [
            'hasData' => true,
            'stats' => compact('totalWatched', 'totalHours', 'remainingMinutes', 'averageRating', 'averagePersonalRating'),
            'chartData' => [
                'genres' => [
                    'labels' => $genreCounts->keys()->toArray(),
                    'data' => $genreCounts->values()->toArray(),
                ],
                'monthly' => [
                    'labels' => $monthlyCounts->keys()->toArray(), // Aylar
                    'data' => $monthlyCounts->values()->toArray(), // İzlenen film sayısı
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
        ]);
    }
}
