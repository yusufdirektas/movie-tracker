<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 📚 ROZET MODELİ
 *
 * Kullanıcıların kazanabileceği başarı rozetlerini temsil eder.
 *
 * @property string $id              Rozet ID'si (örn: 'film-lover')
 * @property string $name            Görünen isim (örn: 'Film Sever')
 * @property string $description     Açıklama
 * @property string $icon            Emoji
 * @property string $requirement_type Koşul türü
 * @property int $requirement_value   Gerekli değer
 * @property string|null $requirement_genre Tür (opsiyonel)
 *
 * @KAVRAM: String Primary Key
 * - Eloquent'te id genellikle integer'dır
 * - String kullanmak için $incrementing = false ve $keyType = 'string' gerekir
 */
class Badge extends Model
{
    // =========================================================================
    // 📚 ROZET KOŞUL TÜRLERİ (Requirement Types)
    // =========================================================================

    public const TYPE_WATCH_COUNT = 'watch_count';           // X film izle
    public const TYPE_GENRE_COUNT = 'genre_count';           // X [tür] filmi izle
    public const TYPE_COMMENT_COUNT = 'comment_count';       // X yorum yap
    public const TYPE_FOLLOW_COUNT = 'follow_count';         // X kişi takip et
    public const TYPE_COLLECTION_COUNT = 'collection_count'; // X koleksiyon oluştur
    public const TYPE_STREAK = 'streak';                     // X gün üst üste film izle
    public const TYPE_FIRST_MOVIE = 'first_movie';           // İlk filmi ekle (milestone)
    public const TYPE_RATING_COUNT = 'rating_count';         // X film puanla

    // =========================================================================
    // 📚 MODEL AYARLARI
    // =========================================================================

    /**
     * @KAVRAM: $incrementing = false
     * - Laravel otomatik artan ID kullanmasın
     * - Biz string ID kullanacağız
     */
    public $incrementing = false;

    /**
     * @KAVRAM: $keyType = 'string'
     * - Primary key'in tipini belirt
     * - find(), findOrFail() doğru çalışsın
     */
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'description',
        'icon',
        'requirement_type',
        'requirement_value',
        'requirement_genre',
        'sort_order',
    ];

    protected $casts = [
        'requirement_value' => 'integer',
        'sort_order' => 'integer',
    ];

    // =========================================================================
    // 📚 İLİŞKİLER (Relationships)
    // =========================================================================

    /**
     * Bu rozete sahip kullanıcılar
     *
     * @KAVRAM: belongsToMany() Parametreleri
     * 1. İlişkili model (User::class)
     * 2. Pivot tablo adı ('user_badges')
     * 3. Bu modelin pivot'taki FK'si ('badge_id')
     * 4. İlişkili modelin pivot'taki FK'si ('user_id')
     *
     * withPivot(): Pivot tablodan ekstra kolon çek (earned_at)
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges', 'badge_id', 'user_id')
            ->withPivot('earned_at');
    }

    // =========================================================================
    // 📚 SORGU KAPSAMLARI (Query Scopes)
    // =========================================================================

    /**
     * Sıralı rozetleri getir
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Belirli türdeki rozetleri getir
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('requirement_type', $type);
    }

    // =========================================================================
    // 📚 YARDIMCI METODLAR
    // =========================================================================

    /**
     * Rozet koşulunun açıklamasını döndür
     */
    public function getRequirementText(): string
    {
        $value = $this->requirement_value;

        return match ($this->requirement_type) {
            self::TYPE_WATCH_COUNT => "{$value} film izle",
            self::TYPE_GENRE_COUNT => "{$value} {$this->requirement_genre} filmi izle",
            self::TYPE_COMMENT_COUNT => "{$value} yorum yap",
            self::TYPE_FOLLOW_COUNT => "{$value} kişi takip et",
            self::TYPE_COLLECTION_COUNT => "{$value} koleksiyon oluştur",
            self::TYPE_STREAK => "{$value} gün üst üste film izle",
            self::TYPE_FIRST_MOVIE => "İlk filmini ekle",
            self::TYPE_RATING_COUNT => "{$value} film puanla",
            default => "Bilinmeyen koşul",
        };
    }

    /**
     * Rozet rengini döndür (UI için)
     */
    public function getColorClass(): string
    {
        return match ($this->requirement_type) {
            self::TYPE_WATCH_COUNT => 'from-indigo-500 to-purple-500',
            self::TYPE_GENRE_COUNT => 'from-pink-500 to-rose-500',
            self::TYPE_COMMENT_COUNT => 'from-blue-500 to-cyan-500',
            self::TYPE_FOLLOW_COUNT => 'from-green-500 to-emerald-500',
            self::TYPE_COLLECTION_COUNT => 'from-teal-500 to-cyan-500',
            self::TYPE_STREAK => 'from-orange-500 to-amber-500',
            self::TYPE_FIRST_MOVIE => 'from-yellow-500 to-amber-500',
            self::TYPE_RATING_COUNT => 'from-violet-500 to-purple-500',
            default => 'from-slate-500 to-slate-600',
        };
    }
}
