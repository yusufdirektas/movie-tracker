<?php

namespace App\Repositories;

use App\Models\Movie;
use App\Repositories\Contracts\MovieRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 📚 REPOSITORY SINIFI
 *
 * Bu sınıf MovieRepositoryInterface'i IMPLEMENT eder.
 * Yani Interface'deki tüm metodları gerçekleştirmek ZORUNDADIR.
 *
 * "implements" anahtar kelimesi:
 * - "Bu sınıf şu sözleşmeye uyacak" demektir
 * - Interface'deki tüm metodlar burada tanımlanmalı
 * - Aksi halde PHP hata verir
 *
 * Bu sınıfın görevi:
 * - Veritabanı ile ilgili TÜM işlemleri yapmak
 * - Controller'ları veritabanı detaylarından soyutlamak
 * - Tekrar kullanılabilir sorgular sağlamak
 */
class MovieRepository implements MovieRepositoryInterface
{
    /**
     * 📚 İZİN VERİLEN SIRALAMA ALANLARI (Whitelist)
     *
     * Neden whitelist?
     * - Kullanıcı URL'den "sort=password" gibi tehlikeli değerler gönderebilir
     * - Whitelist ile sadece izin verilen alanları kabul ederiz (Güvenlik)
     * - SQL Injection ve bilgi sızıntısı önlenir
     */
    protected array $allowedSorts = [
        'updated_at'      => 'desc',
        'title'           => 'asc',
        'rating'          => 'desc',
        'watch_priority'  => 'asc',
        'personal_rating' => 'desc',
        'release_date'    => 'desc',
        'runtime'         => 'desc',
    ];

    /**
     * {@inheritDoc}
     *
     * 📚 Query Builder Zinciri:
     * Her metod bir sorgu parçası ekler ve sorguyu döndürür.
     * Bu sayede metodları zincirleme çağırabiliriz.
     */
    public function getWatchedMovies(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Movie::query()
            ->where('user_id', $userId)
            ->with('collections')  // Eager Loading - N+1 önleme
            ->watched();           // Local Scope

        // Filtreleri uygula
        $query = $this->applyFilters($query, $filters);

        // Favoriler filtresi (sadece watched için)
        if (($filters['filter'] ?? null) === 'favorites') {
            $query->where('personal_rating', '>=', 4);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function getUnwatchedMovies(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Movie::query()
            ->where('user_id', $userId)
            ->select([
                'id',
                'user_id',
                'title',
                'poster_path',
                'rating',
                'director',
                'runtime',
                'release_date',
                'is_watched',
                'watch_priority',
                'updated_at',
            ])
            ->unwatched();

        $query = $this->applyFilters($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * {@inheritDoc}
     */
    public function getAvailableGenres(int $userId, bool $isWatched): Collection
    {
        return Movie::where('user_id', $userId)
            ->where('is_watched', $isWatched)
            ->whereNotNull('genres')
            ->pluck('genres')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * {@inheritDoc}
     */
    public function getStatistics(int $userId): array
    {
        $watchedMovies = Movie::where('user_id', $userId)
            ->watched()
            ->get();

        if ($watchedMovies->isEmpty()) {
            return ['hasData' => false];
        }

        // Temel istatistikler
        $totalWatched = $watchedMovies->count();
        $totalRuntime = $watchedMovies->sum('runtime');
        $totalHours = floor($totalRuntime / 60);
        $remainingMinutes = $totalRuntime % 60;
        $averageRating = $watchedMovies->avg('rating');
        $averagePersonalRating = $watchedMovies->whereNotNull('personal_rating')->avg('personal_rating');

        // Tür dağılımı
        $genreCounts = $watchedMovies->pluck('genres')
            ->filter()
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(8);

        // Aylık izleme
        $monthlyCounts = $watchedMovies->whereNotNull('watched_at')
            ->groupBy(fn($movie) => $movie->watched_at->format('Y-m'))
            ->map(fn($group) => $group->count())
            ->sortKeys();

        // Yönetmen dağılımı
        $directorCounts = $watchedMovies->pluck('director')
            ->filter(fn($d) => !empty($d) && $d !== 'Bilinmiyor')
            ->countBy()
            ->sortDesc()
            ->take(5);

        // Yıl dağılımı
        $releaseYearCounts = $watchedMovies->pluck('release_date')
            ->filter()
            ->map(fn($date) => substr($date, 0, 4))
            ->countBy()
            ->sortKeysDesc()
            ->take(10);

        // 📚 YENİ: Haftalık dağılım (hangi günlerde daha çok izleniyor?)
        // Carbon'un dayOfWeek: 0=Pazar, 1=Pazartesi, ..., 6=Cumartesi
        $weekdayNames = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
        $weekdayCounts = $watchedMovies->whereNotNull('watched_at')
            ->groupBy(fn($movie) => $movie->watched_at->dayOfWeek)
            ->map(fn($group) => $group->count())
            ->sortKeys();

        // Tüm günleri dahil et (0 olanları bile)
        $weekdayData = collect(range(0, 6))->mapWithKeys(function ($day) use ($weekdayCounts, $weekdayNames) {
            return [$weekdayNames[$day] => $weekdayCounts->get($day, 0)];
        });

        // 📚 YENİ: Puan dağılımı (kaç film hangi puan aralığında?)
        $ratingDistribution = $watchedMovies->whereNotNull('rating')
            ->groupBy(fn($movie) => floor($movie->rating))
            ->map(fn($group) => $group->count())
            ->sortKeys();

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
                ],
                'weekdays' => [
                    'labels' => $weekdayData->keys()->toArray(),
                    'data' => $weekdayData->values()->toArray(),
                ],
                'ratings' => [
                    'labels' => $ratingDistribution->keys()->map(fn($r) => $r . '-' . ($r + 1))->toArray(),
                    'data' => $ratingDistribution->values()->toArray(),
                ],
            ]
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function create(int $userId, array $data): Movie
    {
        return Movie::create(array_merge($data, ['user_id' => $userId]));
    }

    /**
     * {@inheritDoc}
     */
    public function update(Movie $movie, array $data): bool
    {
        return $movie->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Movie $movie): bool
    {
        return $movie->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function existsByTmdbId(int $userId, int $tmdbId, string $mediaType = 'movie'): bool
    {
        return Movie::where('user_id', $userId)
            ->where('tmdb_id', $tmdbId)
            ->where('media_type', $mediaType)
            ->exists();
    }

    /**
     * 📚 PRIVATE HELPER METOD
     *
     * Filtreleri sorguya uygular.
     * Bu metod dışarıdan çağrılamaz (private), sadece bu sınıf içinde kullanılır.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyFilters($query, array $filters)
    {
        // Arama filtresi
        if (!empty($filters['search'])) {
            $query->searchByTitle($filters['search']);
        }

        // Tür filtresi
        if (!empty($filters['genre'])) {
            $query->filterByGenre($filters['genre']);
        }

        // Sıralama
        $sort = $filters['sort'] ?? 'updated_at';
        $query->applySort($sort, $this->allowedSorts);

        return $query;
    }
}
