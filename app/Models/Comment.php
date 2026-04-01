<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 📚 COMMENT MODELİ
 *
 * @KAVRAM: MorphTo İlişkisi
 *
 * morphTo() ne yapar?
 * - commentable_type ve commentable_id alanlarına bakarak
 * - İlgili modeli (Movie, Collection vb.) otomatik yükler
 *
 * Örnek:
 *   $comment->commentable // Movie veya Collection döner
 *
 * @KAVRAM: $fillable
 *
 * Mass assignment koruması. Sadece bu alanlar toplu atanabilir.
 * Güvenlik: Kullanıcı gizlice 'user_id' gönderemez.
 *
 * @KAVRAM: $casts
 *
 * Veritabanından çekerken otomatik tip dönüşümü.
 * has_spoiler: 0/1 → false/true
 */
class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'tmdb_id',
        'body',
        'has_spoiler',
    ];

    protected $casts = [
        'has_spoiler' => 'boolean',
    ];

    // ─── İLİŞKİLER ───

    /**
     * Yorumu yazan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Yorumun yapıldığı içerik (Film, Koleksiyon vb.)
     *
     * Polymorphic ilişki:
     * - commentable_type = 'App\Models\Movie' → Movie döner
     * - commentable_type = 'App\Models\Collection' → Collection döner
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Yoruma yapılan tüm reaction'lar (like/dislike)
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(CommentReaction::class);
    }

    // ─── ACCESSOR'LAR (Dinamik Özellikler) ───

    /**
     * @KAVRAM: Accessor
     *
     * Accessor ne yapar?
     * - Veritabanında OLMAYAN bir alan üretir
     * - Her erişimde otomatik hesaplanır
     * - $comment->like_count şeklinde kullanılır
     *
     * Nasıl çalışır?
     * 1. reactions ilişkisinden tüm reaction'ları al
     * 2. where('is_like', true) ile sadece like olanları filtrele
     * 3. count() ile say
     *
     * Örnek:
     *   $comment->like_count // 12
     *   $comment->dislike_count // 3
     *
     * ⚡ Performans İpucu:
     * Accessor her erişimde QUERY ATAR!
     * Çok yorumda kullanmak için withCount() kullan:
     *
     *   Comment::withCount([
     *       'reactions as like_count' => fn($q) => $q->where('is_like', true)
     *   ])->get();
     */
    protected function likeCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->reactions()->where('is_like', true)->count()
        );
    }

    protected function dislikeCount(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->reactions()->where('is_like', false)->count()
        );
    }

    /**
     * Belirli bir kullanıcının bu yoruma reaction'ı var mı?
     *
     * @param  int|null  $userId  Kullanıcı ID (null = giriş yapmamış)
     * @return CommentReaction|null
     */
    public function userReaction(?int $userId): ?CommentReaction
    {
        if (! $userId) {
            return null;
        }

        return $this->reactions()->where('user_id', $userId)->first();
    }
}
