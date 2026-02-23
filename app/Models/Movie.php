<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    // Veritabanına toplu olarak yazılmasına izin verilen alanlar
    protected $fillable = [
    'user_id',
    'tmdb_id',
    'title',
    'director',
    'poster_path',
    'rating',
    'runtime',
    'overview',
    'release_date',
    'is_watched',
];

    // İlişki: Bir film bir kullanıcıya aittir
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
