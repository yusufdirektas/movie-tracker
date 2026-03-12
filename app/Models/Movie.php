<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tmdb_id',
        'title',
        'director',
        'genres',
        'poster_path',
        'rating',
        'personal_rating',
        'runtime',
        'overview',
        'release_date',
        'is_watched',
        'watched_at',
    ];

    protected $casts = [
        'is_watched' => 'boolean',
        'watched_at' => 'date',
        'genres'     => 'array', // JSON → PHP array otomatik dönüşüm
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
            return $query->where('title', 'like', '%' . $search . '%');
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
        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = $defaultSort;
        }

        return $query->orderBy($sort, $allowedSorts[$sort]);
    }

    /**
     * Belirli bir duruma (izlendi/izlenmedi) ait tüm benzersiz türleri getirir.
     * Bu bir scope değil, yardımcı (helper) metodudur (Static Method).
     *
     * @param int $userId
     * @param bool $isWatched
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
}
