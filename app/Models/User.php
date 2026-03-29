<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_public',
        'show_recent_activities',
        'share_token',
        'avatar',
        'bio',
        'showcase_movies',
    ];

    /**
     * Model boot metodu
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->share_token = (string) \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_public' => 'boolean',
            'show_recent_activities' => 'boolean',
            // JSON alanı otomatik array'e çevrilir
            'showcase_movies' => 'array',
        ];
    }

    // =========================================================================
    // 📚 PROFİL YARDIMCI METODLARI
    // =========================================================================

    /**
     * Avatar URL'ini döndür
     *
     * @KAVRAM: Storage::url()
     * - storage/app/public altındaki dosyalara public URL verir
     * - php artisan storage:link ile public/storage symlink oluşturulmalı
     *
     * @return string Avatar URL veya varsayılan avatar
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        // Varsayılan avatar (UI Avatars API - isimden avatar oluşturur)
        $name = urlencode($this->name);

        return "https://ui-avatars.com/api/?name={$name}&background=6366f1&color=fff&size=200";
    }

    /**
     * Vitrin filmlerini Movie modelleri olarak getir
     *
     * @KAVRAM: whereIn() with ordering
     * - showcase_movies array'indeki ID'lere göre filmleri çek
     * - SQLite FIELD desteklemez, PHP tarafında sıralama yap
     */
    public function getShowcaseMoviesAttribute(): \Illuminate\Database\Eloquent\Collection
    {
        // Güvenli şekilde attribute'ü al
        $rawValue = $this->attributes['showcase_movies'] ?? null;
        $movieIds = $rawValue ? json_decode($rawValue, true) : [];
        $movieIds = collect($movieIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if (empty($movieIds)) {
            return new \Illuminate\Database\Eloquent\Collection;
        }

        // Filmleri çek ve PHP'de sırala (SQLite FIELD desteklemediği için)
        // Güvenlik: Sadece bu kullanıcıya ait filmler vitrine girebilir.
        $movies = Movie::where('user_id', $this->id)
            ->whereIn('id', $movieIds)
            ->get();

        // ID sırasını koru
        return $movies->sortBy(function ($movie) use ($movieIds) {
            return array_search($movie->id, $movieIds);
        })->values();
    }

    public function movies()
    {
        return $this->hasMany(Movie::class);
    }

    public function collections()
    {
        return $this->hasMany(Collection::class);
    }

    public function importBatches(): HasMany
    {
        return $this->hasMany(ImportBatch::class);
    }

    // =========================================================================
    // 📚 TAKİP SİSTEMİ İLİŞKİLERİ (Follow System Relationships)
    // =========================================================================
    //
    // Self-referential many-to-many ilişki:
    // - Bir kullanıcı birden fazla kişiyi takip edebilir (following)
    // - Bir kullanıcı birden fazla kişi tarafından takip edilebilir (followers)
    //
    // @KAVRAM: belongsToMany() Parametreleri
    // 1. İlişkili model (User::class)
    // 2. Pivot tablo adı ('follows')
    // 3. Bu modelin pivot'taki foreign key'i ('follower_id' veya 'following_id')
    // 4. İlişkili modelin pivot'taki foreign key'i

    /**
     * Bu kullanıcının TAKİP ETTİĞİ kişiler
     *
     * Örnek: $user->following → Bu kullanıcının takip ettiği herkes
     *
     * follows tablosunda:
     *   follower_id = BU KULLANICI (ben takip ediyorum)
     *   following_id = DİĞER KULLANICI (onu takip ediyorum)
     */
    public function following()
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    /**
     * Bu kullanıcıyı TAKİP EDEN kişiler (takipçiler)
     *
     * Örnek: $user->followers → Bu kullanıcıyı takip eden herkes
     *
     * follows tablosunda:
     *   following_id = BU KULLANICI (beni takip ediyorlar)
     *   follower_id = DİĞER KULLANICI (o beni takip ediyor)
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    // =========================================================================
    // 📚 TAKİP YARDIMCI METODLARI (Follow Helper Methods)
    // =========================================================================

    /**
     * Bir kullanıcıyı takip et
     *
     * @param  User|int  $user  Takip edilecek kullanıcı veya ID
     * @return void
     *
     * Kullanım: $currentUser->follow($otherUser);
     */
    public function follow(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        // Kendini takip edemezsin
        if ($userId === $this->id) {
            return;
        }

        // attach() → pivot tabloya kayıt ekler (zaten varsa hata vermez)
        $this->following()->syncWithoutDetaching([$userId]);
    }

    /**
     * Bir kullanıcıyı takipten çık
     *
     * @param  User|int  $user  Takipten çıkılacak kullanıcı veya ID
     * @return void
     *
     * Kullanım: $currentUser->unfollow($otherUser);
     */
    public function unfollow(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        // detach() → pivot tablodan kaydı siler
        $this->following()->detach($userId);
    }

    /**
     * Bu kullanıcı belirtilen kişiyi takip ediyor mu?
     *
     * @param  User|int  $user  Kontrol edilecek kullanıcı
     * @return bool
     *
     * Kullanım: $currentUser->isFollowing($otherUser)
     */
    public function isFollowing(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->following()->where('following_id', $userId)->exists();
    }

    /**
     * Bu kullanıcıyı belirtilen kişi takip ediyor mu?
     *
     * @param  User|int  $user  Kontrol edilecek kullanıcı
     * @return bool
     *
     * Kullanım: $currentUser->isFollowedBy($otherUser)
     */
    public function isFollowedBy(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->followers()->where('follower_id', $userId)->exists();
    }

    /**
     * Takip edilen kullanıcı sayısı
     */
    public function followingCount(): int
    {
        return $this->following()->count();
    }

    /**
     * Takipçi sayısı
     */
    public function followersCount(): int
    {
        return $this->followers()->count();
    }
}
