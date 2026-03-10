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
}
