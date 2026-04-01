<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 👍👎 COMMENT REACTION MODELİ
 *
 * @KAVRAM: Toggle Pattern (Beğeni/Beğenmeme Mantığı)
 *
 * Nasıl çalışır?
 * 1. Kullanıcı ilk kez beğenirse → Kayıt oluştur (is_like = true)
 * 2. Tekrar beğenirse → Kaydı SİL (unlike)
 * 3. Beğenmeme basarsa → is_like = false olarak GÜNCELLE
 *
 * Örnek:
 *   Like → Database: [comment_id: 5, user_id: 1, is_like: true]
 *   Unlike → Database: Kayıt SİLİNDİ
 *   Like → Dislike → Database: [comment_id: 5, user_id: 1, is_like: false]
 *
 * @KAVRAM: Unique Constraint
 *
 * Migration'da unique(['comment_id', 'user_id']) var.
 * Bir kullanıcı aynı yoruma sadece 1 reaction yapabilir.
 * Hem like hem dislike yapamaz!
 *
 * @KAVRAM: Timestamps (false)
 *
 * Reaction için created_at/updated_at gerekmez.
 * Sadece like mı dislike mı bilmek yeterli.
 */
class CommentReaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'comment_id',
        'user_id',
        'is_like',
    ];

    protected $casts = [
        'is_like' => 'boolean',
    ];

    // ─── İLİŞKİLER ───

    /**
     * Reaction yapan kullanıcı
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Reaction yapılan yorum
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
