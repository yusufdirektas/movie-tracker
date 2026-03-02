<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tmdb_id',
        'title',
        'director',
        'poster_path',
        'rating',
        'personal_rating', // YENİ EKLENDİ
        'runtime',
        'overview',
        'release_date',
        'is_watched',
        'watched_at', // YENİ EKLENDİ
    ];

    // Bu ayar sayesinde veritabanındaki tarih metnini, Laravel otomatik olarak bir Tarih Nesnesine çevirir.
    protected $casts = [
        'is_watched' => 'boolean',
        'watched_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
