<?php

namespace App\Support;

/**
 * 📚 CACHE KEY YÖNETİCİSİ
 *
 * Tüm cache key'lerini merkezi bir yerden yönetmek için kullanılır.
 *
 * @KAVRAM: Cache Key Best Practices
 *
 * 1. NAMESPACE: Key'ler prefix ile gruplanır (movies:, user:, api:)
 *    - Çakışmaları önler
 *    - Toplu invalidation kolaylaşır
 *
 * 2. UNIQUE IDENTIFIER: User ID, Movie ID gibi değerler key'e eklenir
 *    - Her kullanıcı kendi cache'ini görür
 *
 * 3. VERSION: Gerektiğinde key yapısını değiştirmek için versiyon eklenir
 *    - Kod değiştiğinde eski cache'i otomatik geçersiz kılar
 *
 * Kullanım:
 *   Cache::remember(CacheKeys::userStats($userId), CacheKeys::TTL_SHORT, fn() => ...)
 *   Cache::forget(CacheKeys::userStats($userId));
 */
class CacheKeys
{
    // ─── CACHE SÜRELERİ (TTL - Time To Live) ───
    public const TTL_SHORT = 5 * 60;           // 5 dakika - Sık değişen veriler
    public const TTL_MEDIUM = 60 * 60;         // 1 saat - Orta sıklıkta değişen
    public const TTL_LONG = 24 * 60 * 60;      // 24 saat - Nadiren değişen
    public const TTL_WEEK = 7 * 24 * 60 * 60;  // 1 hafta - Statik veriler

    // ─── KULLANICI İLE İLGİLİ KEY'LER ───

    /**
     * Kullanıcı istatistikleri (izlenen film sayısı, toplam süre vb.)
     */
    public static function userStats(int $userId): string
    {
        return "user:{$userId}:stats:v1";
    }

    /**
     * Kullanıcının koleksiyon listesi
     */
    public static function userCollections(int $userId): string
    {
        return "user:{$userId}:collections:v1";
    }

    // ─── FİLM İLE İLGİLİ KEY'LER ───

    /**
     * Film detay sayfasındaki benzer filmler
     */
    public static function similarMovies(int $movieId): string
    {
        return "movie:{$movieId}:similar:v1";
    }

    /**
     * TMDB'den çekilen film detayları
     */
    public static function tmdbMovieDetail(int $tmdbId): string
    {
        return "tmdb:movie:{$tmdbId}:detail:v1";
    }

    // ─── GENEL/GLOBAL KEY'LER ───

    /**
     * Vizyondaki filmler (tüm kullanıcılar için aynı)
     */
    public static function nowPlaying(): string
    {
        return "global:now_playing:v1";
    }

    /**
     * Kullanıcıya özel öneriler (son izlenen filme göre)
     */
    public static function recommendations(int $userId, int $lastMovieId): string
    {
        return "user:{$userId}:recommendations:{$lastMovieId}:v1";
    }

    // ─── INVALIDATION HELPER'LARI ───

    /**
     * Kullanıcının tüm cache'ini temizle (örn: film eklendiğinde)
     *
     * NOT: Bu metod pattern-based silme için tag kullanılması gerekir.
     * Şimdilik tek tek key'leri silmek gerekiyor.
     */
    public static function invalidateUser(int $userId): array
    {
        return [
            self::userStats($userId),
            self::userCollections($userId),
        ];
    }
}
