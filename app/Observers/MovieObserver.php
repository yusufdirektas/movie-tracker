<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Movie;
use App\Services\BadgeService;
use Illuminate\Support\Facades\Cache;

/**
 * 📚 OBSERVER PATTERN (Gözlemci Deseni)
 *
 * Observer, bir modelde değişiklik olduğunda otomatik tetiklenen olaylardır.
 *
 * Ne zaman kullanılır?
 * - Model kaydedildiğinde/silindiğinde otomatik işlem yapılacaksa
 * - Cache temizleme, log tutma, bildirim gönderme gibi "yan etkiler" için
 *
 * Avantajları:
 * - Controller'ları temiz tutar (cache temizleme kodu her yerde tekrarlanmaz)
 * - Tek bir yerden yönetim (DRY prensibi)
 * - Test edilebilirlik
 *
 * ÖRNEK SENARYO:
 * Kullanıcı film ekledi → MovieController::store() çalıştı → Movie::create() oldu
 * → Observer otomatik tetiklendi → Cache temizlendi + Aktivite kaydedildi + Rozetler kontrol edildi
 * → Takipçiler feed'de "Ahmet yeni film ekledi" gördü
 */
class MovieObserver
{
    public function __construct(
        private BadgeService $badgeService
    ) {}

    /**
     * Film oluşturulduğunda
     *
     * @KAVRAM: isDirty() vs wasChanged()
     * - isDirty('field'): Model kaydedilmeden ÖNCE değişti mi? (before save)
     * - wasChanged('field'): Model kaydedildikten SONRA değişti mi? (after save)
     *
     * created() içinde wasChanged kullanmıyoruz çünkü
     * yeni kayıt = tüm alanlar "yeni"
     */
    public function created(Movie $movie): void
    {
        $this->clearUserCache($movie->user_id);

        // Aktivite kaydet: Watchlist'e mi eklendi, yoksa izlendi olarak mı?
        if ($movie->is_watched) {
            Activity::logWatched($movie->user, $movie);
        } else {
            Activity::logAddedToWatchlist($movie->user, $movie);
        }

        // 📚 ROZET KONTROLÜ
        // Film eklendiğinde rozetleri kontrol et (first-movie, watch-count vs.)
        $this->badgeService->checkAndAwardBadges($movie->user);
    }

    /**
     * Film güncellendiğinde
     *
     * @KAVRAM: wasChanged() kullanımı
     * - Sadece gerçekten değişen alanlar için aktivite oluştur
     * - Gereksiz aktivite spam'ini önler
     */
    public function updated(Movie $movie): void
    {
        $this->clearUserCache($movie->user_id);

        // İzlendi durumu değiştiyse ve artık izlendi ise
        if ($movie->wasChanged('is_watched') && $movie->is_watched) {
            Activity::logWatched($movie->user, $movie);
        }

        // Puan değiştiyse ve puan verildiyse
        if ($movie->wasChanged('personal_rating') && $movie->personal_rating !== null) {
            Activity::logRated($movie->user, $movie, $movie->personal_rating);
        }

        // 📚 ROZET KONTROLÜ
        // Film güncellendi, rozetleri kontrol et (izleme sayısı, puan sayısı vs.)
        $this->badgeService->checkAndAwardBadges($movie->user);
    }

    /**
     * Film silindiğinde cache'i temizle
     * (Silinen film için aktivite oluşturmuyoruz - anlamsız olur)
     */
    public function deleted(Movie $movie): void
    {
        $this->clearUserCache($movie->user_id);
    }

    /**
     * Kullanıcıya ait tüm cache'leri temizle
     *
     * 📚 Cache::forget() vs Cache::flush()
     * - forget($key): Sadece belirtilen key'i siler
     * - flush(): TÜM cache'i siler (tehlikeli, diğer kullanıcıların cache'i de gider!)
     *
     * Biz forget() kullanıyoruz çünkü sadece bu kullanıcının cache'ini silmek istiyoruz.
     */
    private function clearUserCache(int $userId): void
    {
        // İstatistik cache'ini temizle
        Cache::forget("user_stats_{$userId}");

        // İleride eklenebilecek diğer cache'ler için buraya ekleyebilirsin:
        // Cache::forget("user_genres_{$userId}");
        // Cache::forget("user_recommendations_{$userId}");
    }
}
