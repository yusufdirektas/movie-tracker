<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 📚 AKTİVİTE MODELİ
 *
 * Kullanıcıların yaptığı tüm aksiyonları temsil eder.
 * Feed sisteminin temel yapı taşıdır.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 *
 * @KAVRAM: Aktivite Türleri (Type)
 * - watched: Film izlendi
 * - rated: Film puanlandı
 * - added_to_watchlist: Watchlist'e eklendi
 * - commented: Yorum yapıldı
 * - followed: Kullanıcı takip edildi
 * - created_collection: Koleksiyon oluşturuldu
 */
class Activity extends Model
{
    use HasFactory;

    // =========================================================================
    // 📚 AKTİVİTE TÜRLERİ SABİTLERİ (Constants)
    // =========================================================================
    //
    // @KAVRAM: Class Constants
    // - Magic string'leri önler (typo hataları azalır)
    // - IDE autocomplete desteği sağlar
    // - Tek yerden değişiklik yapılabilir
    //
    // Kullanım: Activity::TYPE_WATCHED

    public const TYPE_WATCHED = 'watched';
    public const TYPE_RATED = 'rated';
    public const TYPE_ADDED_TO_WATCHLIST = 'added_to_watchlist';
    public const TYPE_COMMENTED = 'commented';
    public const TYPE_FOLLOWED = 'followed';
    public const TYPE_CREATED_COLLECTION = 'created_collection';

    /**
     * Tüm türleri liste olarak al (validation için kullanışlı)
     */
    public static function getAllTypes(): array
    {
        return [
            self::TYPE_WATCHED,
            self::TYPE_RATED,
            self::TYPE_ADDED_TO_WATCHLIST,
            self::TYPE_COMMENTED,
            self::TYPE_FOLLOWED,
            self::TYPE_CREATED_COLLECTION,
        ];
    }

    protected $fillable = [
        'user_id',
        'type',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    protected $casts = [
        // JSON kolonu → PHP array otomatik dönüşümü
        'metadata' => 'array',
    ];

    // =========================================================================
    // 📚 İLİŞKİLER (Relationships)
    // =========================================================================

    /**
     * Aktiviteyi yapan kullanıcı
     *
     * Örnek: $activity->user->name → "Ahmet"
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 📚 POLYMORPHİC İLİŞKİ: morphTo()
     *
     * @KAVRAM: morphTo() ne yapar?
     * - subject_type kolonuna bakarak hangi model olduğunu anlar
     * - subject_id ile o modeli çeker
     * - Tek ilişki tanımı ile farklı modellere erişim sağlar
     *
     * Örnek Veriler:
     *   subject_type = 'App\Models\Movie', subject_id = 123 → Movie::find(123)
     *   subject_type = 'App\Models\User', subject_id = 456 → User::find(456)
     *
     * Kullanım:
     *   $activity->subject → Movie|User|Collection|null döner
     *   $activity->subject->title → Film başlığı (eğer Movie ise)
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // =========================================================================
    // 📚 FABRİKA METODLARI (Factory Methods)
    // =========================================================================
    //
    // @KAVRAM: Factory Method Pattern
    // - Nesne oluşturmayı kapsüller
    // - Kodun okunabilirliğini artırır
    // - İş mantığını tek yerde toplar
    //
    // Kullanım:
    //   Activity::logWatched($user, $movie);
    //   Activity::logRated($user, $movie, 8.5);

    /**
     * Film izlendi aktivitesi oluştur
     */
    public static function logWatched(User $user, Movie $movie): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_WATCHED,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
            'metadata' => [
                'title' => $movie->title,
                'poster_path' => $movie->poster_path,
            ],
        ]);
    }

    /**
     * Film puanlandı aktivitesi oluştur
     */
    public static function logRated(User $user, Movie $movie, float $rating): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_RATED,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
            'metadata' => [
                'title' => $movie->title,
                'poster_path' => $movie->poster_path,
                'rating' => $rating,
            ],
        ]);
    }

    /**
     * Watchlist'e eklendi aktivitesi oluştur
     */
    public static function logAddedToWatchlist(User $user, Movie $movie): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_ADDED_TO_WATCHLIST,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
            'metadata' => [
                'title' => $movie->title,
                'poster_path' => $movie->poster_path,
            ],
        ]);
    }

    /**
     * Yorum yapıldı aktivitesi oluştur
     */
    public static function logCommented(User $user, Comment $comment, Movie $movie): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_COMMENTED,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
            'metadata' => [
                'title' => $movie->title,
                'poster_path' => $movie->poster_path,
                'comment_preview' => \Illuminate\Support\Str::limit($comment->body, 100),
                'has_spoiler' => $comment->has_spoiler,
            ],
        ]);
    }

    /**
     * Kullanıcı takip edildi aktivitesi oluştur
     */
    public static function logFollowed(User $follower, User $following): self
    {
        return self::create([
            'user_id' => $follower->id,
            'type' => self::TYPE_FOLLOWED,
            'subject_type' => User::class,
            'subject_id' => $following->id,
            'metadata' => [
                'name' => $following->name,
            ],
        ]);
    }

    /**
     * Koleksiyon oluşturuldu aktivitesi oluştur
     */
    public static function logCreatedCollection(User $user, Collection $collection): self
    {
        return self::create([
            'user_id' => $user->id,
            'type' => self::TYPE_CREATED_COLLECTION,
            'subject_type' => Collection::class,
            'subject_id' => $collection->id,
            'metadata' => [
                'name' => $collection->name,
            ],
        ]);
    }

    // =========================================================================
    // 📚 SORGU KAPSAMI (Query Scopes)
    // =========================================================================

    /**
     * Belirli kullanıcıların aktivitelerini getir
     *
     * @param  array  $userIds  Kullanıcı ID listesi
     *
     * Kullanım: Activity::forUsers([1, 2, 3])->get();
     */
    public function scopeForUsers($query, array $userIds)
    {
        return $query->whereIn('user_id', $userIds);
    }

    /**
     * Belirli türdeki aktiviteleri getir
     *
     * Kullanım: Activity::ofType('watched')->get();
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Son X gündeki aktiviteleri getir
     *
     * Kullanım: Activity::recentDays(7)->get();
     */
    public function scopeRecentDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // =========================================================================
    // 📚 GÖRÜNTÜLEME YARDIMCILARI (Display Helpers)
    // =========================================================================

    /**
     * Aktivite türünün Türkçe açıklamasını döndür
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_WATCHED => 'izledi',
            self::TYPE_RATED => 'puanladı',
            self::TYPE_ADDED_TO_WATCHLIST => 'izleme listesine ekledi',
            self::TYPE_COMMENTED => 'yorum yaptı',
            self::TYPE_FOLLOWED => 'takip etmeye başladı',
            self::TYPE_CREATED_COLLECTION => 'koleksiyon oluşturdu',
            default => 'bir şey yaptı',
        };
    }

    /**
     * Aktivite türünün ikonunu döndür (emoji)
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            self::TYPE_WATCHED => '👁️',
            self::TYPE_RATED => '⭐',
            self::TYPE_ADDED_TO_WATCHLIST => '📝',
            self::TYPE_COMMENTED => '💬',
            self::TYPE_FOLLOWED => '👥',
            self::TYPE_CREATED_COLLECTION => '📚',
            default => '📌',
        };
    }
}
