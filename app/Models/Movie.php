<?php

namespace App\Models;

use App\Observers\MovieObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 📚 #[ObservedBy] ATTRIBUTE (PHP 8 Özelliği)
 *
 * Bu attribute, Movie modeline MovieObserver'ı otomatik bağlar.
 * Artık AppServiceProvider'da manuel kayıt yapmaya gerek yok.
 *
 * Eski yöntem (Laravel 10 öncesi):
 *   // AppServiceProvider.php içinde:
 *   Movie::observe(MovieObserver::class);
 *
 * Yeni yöntem (Laravel 10+):
 *   Model sınıfının üstüne #[ObservedBy] ekle, bitti!
 */
#[ObservedBy([MovieObserver::class])]
class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tmdb_id',
        'media_type',
        'title',
        'director',
        'genres',
        'poster_path',
        'rating',
        'personal_rating',
        'personal_note',
        'runtime',
        'overview',
        'release_date',
        'is_watched',
        'watched_at',
    ];

    protected $casts = [
        'is_watched' => 'boolean',
        'watched_at' => 'date',
        'release_date' => 'date',
        'genres' => 'array', // JSON → PHP array otomatik dönüşüm
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_movie')
            ->withTimestamps();
    }

    // =========================================================================
    // 📚 LOCAL SCOPES (Yerel Kapsamlar) - EĞİTİM BÖLÜMÜ
    // =========================================================================
    //
    // Local Scope'lar, veritabanı sorgularının tekrar eden kısımlarını
    // Model içine taşıyarak DRY (Don't Repeat Yourself) prensibini uygulamamızı sağlar.
    // Controller'ları temizler ve kodun okunabilirliğini artırır.
    // Scope metodları her zaman "scope" kelimesiyle başlar ve Builder objesini alır/döndürür.

    /**
     * Sadece izlenen filmleri getirir.
     * Kullanım: Movie::watched()->get();
     */
    public function scopeWatched($query)
    {
        return $query->where('is_watched', true);
    }

    /**
     * Sadece izlenmeyen (izleme listesindeki) filmleri getirir.
     * Kullanım: Movie::unwatched()->get();
     */
    public function scopeUnwatched($query)
    {
        return $query->where('is_watched', false);
    }

    /**
     * Film adına göre arama yapar (SQL LIKE kullanarak).
     * Kullanım: Movie::searchByTitle('matr')->get();
     */
    public function scopeSearchByTitle($query, $search)
    {
        if ($search) {
            return $query->where('title', 'like', '%'.$search.'%');
        }

        return $query;
    }

    /**
     * Belirli bir türe göre filtreleme yapar (JSON kolonu içinde arar).
     * Kullanım: Movie::filterByGenre('Aksiyon')->get();
     */
    public function scopeFilterByGenre($query, $genre)
    {
        if ($genre) {
            return $query->whereJsonContains('genres', $genre);
        }

        return $query;
    }

    /**
     * Sıralama işlemini tekilleştirip güvenli hale getirir (Whitelist yaklaşımı).
     * Kullanım: Movie::applySort('title', $allowedSorts, 'updated_at')->get();
     */
    public function scopeApplySort($query, $sort, array $allowedSorts, string $defaultSort = 'updated_at')
    {
        if (! array_key_exists($sort, $allowedSorts)) {
            $sort = $defaultSort;
        }

        return $query->orderBy($sort, $allowedSorts[$sort]);
    }

    // =========================================================================
    // 📚 GELİŞMİŞ ARAMA SCOPE'LARI (Advanced Search Scopes)
    // =========================================================================
    //
    // Bu scope'lar gelişmiş filtreleme için kullanılır.
    // Her biri tek bir sorumluluğa sahiptir (Single Responsibility Principle).
    // Zincirleme kullanılabilirler: Movie::filterByYear(2020)->filterByRating(7)->get()

    /**
     * 📚 YIL ARALIĞI FİLTRESİ
     *
     * BETWEEN operatörü: Belirli bir aralıktaki değerleri seçer.
     * whereYear() yerine whereBetween kullanıyoruz çünkü release_date string olabilir.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $yearFrom  Başlangıç yılı (örn: 1990)
     * @param  int|null  $yearTo  Bitiş yılı (örn: 2000)
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * Kullanım: Movie::filterByYearRange(1990, 2000)->get();
     */
    public function scopeFilterByYearRange($query, $yearFrom = null, $yearTo = null)
    {
        // Sadece dolu değerler için filtre uygula
        if ($yearFrom) {
            $query->where('release_date', '>=', $yearFrom.'-01-01');
        }
        if ($yearTo) {
            $query->where('release_date', '<=', $yearTo.'-12-31');
        }

        return $query;
    }

    /**
     * 📚 SÜRE ARALIĞI FİLTRESİ (dakika cinsinden)
     *
     * Kullanıcı "90-120 dakika arası filmler" gibi filtreleme yapabilir.
     *
     * @param  int|null  $minRuntime  Minimum süre (dakika)
     * @param  int|null  $maxRuntime  Maksimum süre (dakika)
     *
     * Kullanım: Movie::filterByRuntime(90, 120)->get();
     */
    public function scopeFilterByRuntime($query, $minRuntime = null, $maxRuntime = null)
    {
        if ($minRuntime) {
            $query->where('runtime', '>=', $minRuntime);
        }
        if ($maxRuntime) {
            $query->where('runtime', '<=', $maxRuntime);
        }

        return $query;
    }

    /**
     * 📚 TMDB PUAN FİLTRESİ
     *
     * TMDB puanı 0-10 arasındadır.
     * Kullanıcı "7+ puan" gibi filtreleme yapabilir.
     *
     * @param  float|null  $minRating  Minimum TMDB puanı
     * @param  float|null  $maxRating  Maksimum TMDB puanı
     *
     * Kullanım: Movie::filterByRating(7.0)->get();
     */
    public function scopeFilterByRating($query, $minRating = null, $maxRating = null)
    {
        if ($minRating !== null) {
            $query->where('rating', '>=', $minRating);
        }
        if ($maxRating !== null) {
            $query->where('rating', '<=', $maxRating);
        }

        return $query;
    }

    /**
     * 📚 YÖNETMEN FİLTRESİ
     *
     * LIKE operatörü ile kısmi eşleşme yapılır.
     * "Nolan" yazılırsa "Christopher Nolan" da bulunur.
     *
     * @param  string|null  $director  Yönetmen adı (kısmi olabilir)
     *
     * Kullanım: Movie::filterByDirector('Nolan')->get();
     */
    public function scopeFilterByDirector($query, $director = null)
    {
        if ($director) {
            return $query->where('director', 'like', '%'.$director.'%');
        }

        return $query;
    }

    /**
     * 📚 MEDYA TİPİ FİLTRESİ (Film veya Dizi)
     *
     * @param  string|null  $mediaType  'movie' veya 'tv'
     *
     * Kullanım: Movie::filterByMediaType('movie')->get();
     */
    public function scopeFilterByMediaType($query, $mediaType = null)
    {
        if ($mediaType && in_array($mediaType, ['movie', 'tv'])) {
            return $query->where('media_type', $mediaType);
        }

        return $query;
    }

    /**
     * Belirli bir duruma (izlendi/izlenmedi) ait tüm benzersiz türleri getirir.
     * Bu bir scope değil, yardımcı (helper) metodudur (Static Method).
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailableGenres(int $userId, bool $isWatched)
    {
        return self::where('user_id', $userId)
            ->where('is_watched', $isWatched)
            ->whereNotNull('genres')
            ->pluck('genres')
            ->flatten()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * 📚 BENZERSİZ YÖNETMENLERİ GETİR
     *
     * Kullanıcının arşivindeki tüm benzersiz yönetmenleri listeler.
     * Gelişmiş arama formundaki dropdown için kullanılır.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAvailableDirectors(int $userId, bool $isWatched)
    {
        return self::where('user_id', $userId)
            ->where('is_watched', $isWatched)
            ->whereNotNull('director')
            ->where('director', '!=', 'Bilinmiyor')
            ->pluck('director')
            ->unique()
            ->sort()
            ->values();
    }
}
