<?php

namespace App\Observers;

use App\Models\Movie;
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
 * → Observer otomatik tetiklendi → Cache temizlendi
 * → Kullanıcı istatistik sayfasına gitti → Güncel veri gösterildi
 */
class MovieObserver
{
    /**
     * Film oluşturulduğunda cache'i temizle
     *
     * 📚 Neden temizliyoruz?
     * Yeni film eklendi = İstatistikler değişti (film sayısı, toplam süre vs.)
     * Eski cache'deki veriler artık yanlış, temizlememiz lazım.
     */
    public function created(Movie $movie): void
    {
        $this->clearUserCache($movie->user_id);
    }

    /**
     * Film güncellendiğinde cache'i temizle
     *
     * 📚 Neden temizliyoruz?
     * Film izlendi olarak işaretlendi veya puan verildi
     * = İstatistikler değişti
     */
    public function updated(Movie $movie): void
    {
        $this->clearUserCache($movie->user_id);
    }

    /**
     * Film silindiğinde cache'i temizle
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
