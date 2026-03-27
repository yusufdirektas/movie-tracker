<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 📚 MANY-TO-MANY İLİŞKİ (Çoktan Çoğa)
 *
 * Bir koleksiyonda birden fazla film olabilir.
 * Bir film de birden fazla koleksiyonda olabilir.
 * Bu ilişki "collection_movie" pivot tablosuyla yönetilir.
 *
 * Kullanım:
 *   $collection->movies;           → Bu koleksiyondaki filmler
 *   $movie->collections;           → Bu filmin dahil olduğu koleksiyonlar
 *   $collection->movies()->attach($movieId);   → Filme koleksiyon ekle
 *   $collection->movies()->detach($movieId);   → Filmden koleksiyonu çıkar
 */
class Collection extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'icon',
        'color',
        'is_public',
        'share_token',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Model boot metodu
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($collection) {
            $collection->share_token = (string) \Illuminate\Support\Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class, 'collection_movie')
            ->withPivot('sort_order')
            ->orderBy('collection_movie.sort_order')
            ->withTimestamps();
    }
}
