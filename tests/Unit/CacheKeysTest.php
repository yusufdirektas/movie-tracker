<?php

namespace Tests\Unit;

use App\Support\CacheKeys;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * 📚 UNIT TEST ÖRNEĞİ
 *
 * Unit testler:
 * - Veritabanı KULLANMAZ (hızlı çalışır)
 * - Tek bir sınıfı/fonksiyonu test eder
 * - PHPUnit\Framework\TestCase extend eder (Laravel TestCase değil)
 *
 * Feature testlerden farkı:
 * - Feature: HTTP request → response (entegrasyon)
 * - Unit: Tek fonksiyon → çıktı (izole)
 *
 * PHPUnit 11+ Attributes:
 * - #[Test] → metodun test olduğunu belirtir
 * - #[Group('cache')] → testleri gruplar (--group cache ile çalıştır)
 */
#[Group('cache')]
class CacheKeysTest extends TestCase
{
    // ─── TTL SABİTLERİ TESTİ ───

    /**
     * TTL sabitleri saniye cinsinden doğru değerlere sahip mi?
     */
    #[Test]
    public function ttl_constants_have_correct_values(): void
    {
        // 5 dakika = 300 saniye
        $this->assertEquals(300, CacheKeys::TTL_SHORT);

        // 1 saat = 3600 saniye
        $this->assertEquals(3600, CacheKeys::TTL_MEDIUM);

        // 24 saat = 86400 saniye
        $this->assertEquals(86400, CacheKeys::TTL_LONG);

        // 1 hafta = 604800 saniye
        $this->assertEquals(604800, CacheKeys::TTL_WEEK);
    }

    // ─── KEY FORMAT TESTLERİ ───

    /**
     * userStats() doğru formatta key üretiyor mu?
     */
    #[Test]
    public function user_stats_generates_correct_key_format(): void
    {
        $key = CacheKeys::userStats(123);

        // Key formatı: user:{id}:stats:v1
        $this->assertEquals('user:123:stats:v1', $key);

        // Farklı ID'ler farklı key'ler üretmeli
        $this->assertNotEquals(
            CacheKeys::userStats(123),
            CacheKeys::userStats(456)
        );
    }

    #[Test]
    public function user_collections_generates_correct_key_format(): void
    {
        $key = CacheKeys::userCollections(42);

        $this->assertEquals('user:42:collections:v1', $key);
    }

    #[Test]
    public function similar_movies_generates_correct_key_format(): void
    {
        $key = CacheKeys::similarMovies(550); // Fight Club TMDB ID

        $this->assertEquals('movie:550:similar:v1', $key);
    }

    #[Test]
    public function tmdb_movie_detail_generates_correct_key_format(): void
    {
        $key = CacheKeys::tmdbMovieDetail(550);

        $this->assertEquals('tmdb:movie:550:detail:v1', $key);
    }

    #[Test]
    public function now_playing_generates_global_key(): void
    {
        $key = CacheKeys::nowPlaying();

        // Global key - kullanıcıya bağlı değil
        $this->assertEquals('global:now_playing:v1', $key);
    }

    #[Test]
    public function recommendations_includes_user_and_movie_id(): void
    {
        $key = CacheKeys::recommendations(10, 550);

        // Hem kullanıcı hem film ID içermeli
        $this->assertEquals('user:10:recommendations:550:v1', $key);

        // Farklı kullanıcı = farklı key
        $this->assertNotEquals(
            CacheKeys::recommendations(10, 550),
            CacheKeys::recommendations(20, 550)
        );

        // Farklı film = farklı key
        $this->assertNotEquals(
            CacheKeys::recommendations(10, 550),
            CacheKeys::recommendations(10, 551)
        );
    }

    // ─── INVALIDATION TESTİ ───

    #[Test]
    public function invalidate_user_returns_array_of_user_keys(): void
    {
        $keys = CacheKeys::invalidateUser(99);

        $this->assertIsArray($keys);
        $this->assertContains('user:99:stats:v1', $keys);
        $this->assertContains('user:99:collections:v1', $keys);
    }

    // ─── KEY UNIQUENESS TESTİ ───

    /**
     * Farklı metodlar çakışmayan key'ler üretmeli
     */
    #[Test]
    public function different_methods_produce_unique_keys(): void
    {
        $keys = [
            CacheKeys::userStats(1),
            CacheKeys::userCollections(1),
            CacheKeys::similarMovies(1),
            CacheKeys::tmdbMovieDetail(1),
            CacheKeys::nowPlaying(),
            CacheKeys::recommendations(1, 1),
        ];

        // Tüm key'ler unique olmalı
        $uniqueKeys = array_unique($keys);
        $this->assertCount(count($keys), $uniqueKeys);
    }
}
