<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
