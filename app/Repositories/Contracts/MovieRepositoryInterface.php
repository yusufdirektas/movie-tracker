<?php

namespace App\Repositories\Contracts;

use App\Models\Movie;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * 📚 INTERFACE (Arayüz) NEDİR?
 *
 * Interface, bir sınıfın NASIL davranması gerektiğini tanımlayan bir "sözleşme"dir.
 * Metodların imzalarını (isim, parametreler, dönüş tipi) belirler,
 * ama implementasyonu (içeriği) belirlemez.
 *
 * Neden kullanıyoruz?
 *
 * 1. DEPENDENCY INVERSION PRINCIPLE (SOLID'in D'si):
 *    - Yüksek seviyeli modüller (Controller) düşük seviyeli modüllere (Repository) bağımlı olmamalı
 *    - İkisi de soyutlamalara (Interface) bağımlı olmalı
 *
 * 2. TEST EDİLEBİLİRLİK:
 *    - Test yazarken gerçek veritabanı yerine "fake" repository kullanabiliriz
 *    - Interface aynı kaldığı sürece sistem çalışır
 *
 * 3. DEĞİŞTİRİLEBİLİRLİK:
 *    - Yarın MySQL yerine MongoDB kullanmak istersek, sadece yeni bir
 *      MongoMovieRepository yazarız, Interface aynı kalır
 *    - Controller hiç değişmez!
 *
 * ÖRNEK:
 *   interface PaymentGatewayInterface {
 *       public function charge(float $amount): bool;
 *   }
 *
 *   class StripePayment implements PaymentGatewayInterface { ... }
 *   class PayPalPayment implements PaymentGatewayInterface { ... }
 *
 *   // Controller hangisini kullandığını bilmez, sadece Interface'e güvenir
 */
interface MovieRepositoryInterface
{
    /**
     * Kullanıcının izlediği filmleri getirir (sayfalı)
     *
     * @param int $userId Kullanıcı ID'si
     * @param array $filters Filtreler (search, genre, sort, filter)
     * @param int $perPage Sayfa başına kayıt
     * @return LengthAwarePaginator
     */
    public function getWatchedMovies(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Kullanıcının izleyeceği filmleri getirir (watchlist)
     *
     * @param int $userId Kullanıcı ID'si
     * @param array $filters Filtreler
     * @param int $perPage Sayfa başına kayıt
     * @return LengthAwarePaginator
     */
    public function getUnwatchedMovies(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     * Kullanıcının filmlerindeki benzersiz türleri getirir
     *
     * @param int $userId Kullanıcı ID'si
     * @param bool $isWatched İzlenen mi izlenecek mi
     * @return Collection
     */
    public function getAvailableGenres(int $userId, bool $isWatched): Collection;

    /**
     * Kullanıcının film istatistiklerini getirir
     *
     * @param int $userId Kullanıcı ID'si
     * @return array
     */
    public function getStatistics(int $userId): array;

    /**
     * Film oluşturur
     *
     * @param int $userId Kullanıcı ID'si
     * @param array $data Film verileri
     * @return Movie
     */
    public function create(int $userId, array $data): Movie;

    /**
     * Film günceller
     *
     * @param Movie $movie Film modeli
     * @param array $data Güncellenecek veriler
     * @return bool
     */
    public function update(Movie $movie, array $data): bool;

    /**
     * Film siler
     *
     * @param Movie $movie Film modeli
     * @return bool
     */
    public function delete(Movie $movie): bool;

    /**
     * Kullanıcının belirli bir TMDB ID'li filmi var mı kontrol eder
     *
     * @param int $userId Kullanıcı ID'si
     * @param int $tmdbId TMDB ID
     * @param string $mediaType Film veya dizi
     * @return bool
     */
    public function existsByTmdbId(int $userId, int $tmdbId, string $mediaType = 'movie'): bool;
}
