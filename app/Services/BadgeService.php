<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 📚 ROZET SERVİSİ
 *
 * Rozet kontrolü ve verme işlemlerini yönetir.
 *
 * @KAVRAM: Service Pattern
 * - İş mantığını Controller'dan ayırır
 * - Test edilebilirliği artırır
 * - Kodun tekrar kullanılabilirliğini sağlar
 * - "Fat Model, Skinny Controller" yerine "Service Layer" yaklaşımı
 *
 * Kullanım:
 *   $service = new BadgeService();
 *   $newBadges = $service->checkAndAwardBadges($user);
 */
class BadgeService
{
    /**
     * Kullanıcının tüm rozetlerini kontrol et ve hak edildiyse ver
     *
     * @param User $user Kontrol edilecek kullanıcı
     * @return Collection Yeni kazanılan rozetler
     */
    public function checkAndAwardBadges(User $user): Collection
    {
        $awarded = collect();

        // Tüm rozetleri çek
        $badges = Badge::all();

        foreach ($badges as $badge) {
            // Zaten sahipse atla
            if ($user->hasBadge($badge->id)) {
                continue;
            }

            // Koşulu kontrol et
            if ($this->meetsRequirement($user, $badge)) {
                $user->awardBadge($badge->id);
                $awarded->push($badge);
            }
        }

        return $awarded;
    }

    /**
     * Kullanıcı rozet koşulunu sağlıyor mu?
     *
     * @KAVRAM: match() Expression
     * - PHP 8'de switch'in modern versiyonu
     * - Değer döndürür
     * - break gerekmez
     */
    public function meetsRequirement(User $user, Badge $badge): bool
    {
        return match ($badge->requirement_type) {
            Badge::TYPE_FIRST_MOVIE => $this->checkFirstMovie($user),
            Badge::TYPE_WATCH_COUNT => $this->checkWatchCount($user, $badge->requirement_value),
            Badge::TYPE_GENRE_COUNT => $this->checkGenreCount($user, $badge->requirement_genre, $badge->requirement_value),
            Badge::TYPE_COMMENT_COUNT => $this->checkCommentCount($user, $badge->requirement_value),
            Badge::TYPE_FOLLOW_COUNT => $this->checkFollowCount($user, $badge->requirement_value),
            Badge::TYPE_COLLECTION_COUNT => $this->checkCollectionCount($user, $badge->requirement_value),
            Badge::TYPE_RATING_COUNT => $this->checkRatingCount($user, $badge->requirement_value),
            Badge::TYPE_STREAK => $this->checkStreak($user, $badge->requirement_value),
            default => false,
        };
    }

    // =========================================================================
    // 📚 KOŞUL KONTROL METODLARI
    // =========================================================================

    /**
     * İlk film eklendi mi?
     */
    private function checkFirstMovie(User $user): bool
    {
        return $user->movies()->exists();
    }

    /**
     * Belirli sayıda film izlendi mi?
     */
    private function checkWatchCount(User $user, int $required): bool
    {
        return $user->movies()->where('is_watched', true)->count() >= $required;
    }

    /**
     * Belirli türde yeterli film izlendi mi?
     *
     * @KAVRAM: whereJsonContains()
     * - JSON kolonunda değer arar
     * - genres = ['Korku', 'Gerilim'] → whereJsonContains('genres', 'Korku') → true
     */
    private function checkGenreCount(User $user, string $genre, int $required): bool
    {
        return $user->movies()
            ->where('is_watched', true)
            ->whereJsonContains('genres', $genre)
            ->count() >= $required;
    }

    /**
     * Yeterli yorum yapıldı mı?
     */
    private function checkCommentCount(User $user, int $required): bool
    {
        // Kullanıcının yaptığı toplam yorum sayısını kontrol et
        return \App\Models\Comment::where('user_id', $user->id)->count() >= $required;
    }

    /**
     * Yeterli kişi takip ediliyor mu?
     */
    private function checkFollowCount(User $user, int $required): bool
    {
        return $user->following()->count() >= $required;
    }

    /**
     * Yeterli koleksiyon oluşturuldu mu?
     */
    private function checkCollectionCount(User $user, int $required): bool
    {
        return $user->collections()->count() >= $required;
    }

    /**
     * Yeterli film puanlandı mı?
     */
    private function checkRatingCount(User $user, int $required): bool
    {
        return $user->movies()
            ->whereNotNull('personal_rating')
            ->count() >= $required;
    }

    /**
     * Üst üste X gün film izlendi mi?
     *
     * @KAVRAM: Streak Hesaplama
     * - Son X günde her gün en az 1 film izlenmeli
     * - watched_at alanını kullanıyoruz
     */
    private function checkStreak(User $user, int $requiredDays): bool
    {
        // Son X güne ait izleme tarihlerini al
        $dates = $user->movies()
            ->where('is_watched', true)
            ->whereNotNull('watched_at')
            ->where('watched_at', '>=', now()->subDays($requiredDays))
            ->pluck('watched_at')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();

        // Son X günün hepsinde izleme var mı?
        $streak = 0;
        $checkDate = now()->startOfDay();

        for ($i = 0; $i < $requiredDays; $i++) {
            $dateStr = $checkDate->format('Y-m-d');
            if ($dates->contains($dateStr)) {
                $streak++;
            } else {
                break; // Seri bozuldu
            }
            $checkDate->subDay();
        }

        return $streak >= $requiredDays;
    }

    // =========================================================================
    // 📚 KULLANICI İSTATİSTİKLERİ (Rozet İlerlemesi için)
    // =========================================================================

    /**
     * Kullanıcının rozet ilerlemesini hesapla
     *
     * @param User $user Kullanıcı
     * @param Badge|null $badge Belirli bir rozet (opsiyonel)
     * @return array Tek rozet için progress array, hepsi için progress array listesi
     */
    public function getBadgeProgress(User $user, ?Badge $badge = null): array
    {
        // Tek rozet için
        if ($badge) {
            $current = $this->getCurrentProgress($user, $badge);
            $target = $badge->requirement_value;
            $isEarned = $user->hasBadge($badge->id);

            return [
                'current' => $current,
                'target' => $target,
                'percentage' => min(100, round(($current / max(1, $target)) * 100)),
                'is_earned' => $isEarned,
            ];
        }

        // Tüm rozetler için
        $badges = Badge::ordered()->get();
        $progress = [];

        foreach ($badges as $badge) {
            $current = $this->getCurrentProgress($user, $badge);
            $target = $badge->requirement_value;
            $isEarned = $user->hasBadge($badge->id);

            $progress[] = [
                'badge' => $badge,
                'current' => $current,
                'target' => $target,
                'percentage' => min(100, round(($current / max(1, $target)) * 100)),
                'is_earned' => $isEarned,
            ];
        }

        return $progress;
    }

    /**
     * Belirli bir rozet için mevcut ilerlemeyi hesapla
     */
    private function getCurrentProgress(User $user, Badge $badge): int
    {
        return match ($badge->requirement_type) {
            Badge::TYPE_FIRST_MOVIE => $user->movies()->exists() ? 1 : 0,
            Badge::TYPE_WATCH_COUNT => $user->movies()->where('is_watched', true)->count(),
            Badge::TYPE_GENRE_COUNT => $user->movies()
                ->where('is_watched', true)
                ->whereJsonContains('genres', $badge->requirement_genre)
                ->count(),
            Badge::TYPE_COMMENT_COUNT => \App\Models\Comment::where('user_id', $user->id)->count(),
            Badge::TYPE_FOLLOW_COUNT => $user->following()->count(),
            Badge::TYPE_COLLECTION_COUNT => $user->collections()->count(),
            Badge::TYPE_RATING_COUNT => $user->movies()->whereNotNull('personal_rating')->count(),
            Badge::TYPE_STREAK => $this->getCurrentStreak($user),
            default => 0,
        };
    }

    /**
     * Mevcut streak'i hesapla
     */
    private function getCurrentStreak(User $user): int
    {
        $dates = $user->movies()
            ->where('is_watched', true)
            ->whereNotNull('watched_at')
            ->orderByDesc('watched_at')
            ->pluck('watched_at')
            ->map(fn($date) => $date->format('Y-m-d'))
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $checkDate = now()->startOfDay();

        // Bugün veya dün izleme yoksa streak 0
        $today = $checkDate->format('Y-m-d');
        $yesterday = $checkDate->copy()->subDay()->format('Y-m-d');

        if (!$dates->contains($today) && !$dates->contains($yesterday)) {
            return 0;
        }

        // Streak hesapla
        foreach ($dates as $date) {
            if ($date === $checkDate->format('Y-m-d')) {
                $streak++;
                $checkDate->subDay();
            } elseif ($date === $checkDate->copy()->subDay()->format('Y-m-d')) {
                // Bir gün atlandı, seri devam ediyor olabilir
                $checkDate->subDay();
                if ($date === $checkDate->format('Y-m-d')) {
                    $streak++;
                    $checkDate->subDay();
                }
            } else {
                break;
            }
        }

        return $streak;
    }
}
