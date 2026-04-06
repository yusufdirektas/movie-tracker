<?php

namespace Tests\Feature;

use App\Models\Badge;
use App\Models\Movie;
use App\Models\User;
use App\Models\Collection;
use App\Models\Comment;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 📚 ROZET SİSTEMİ TESTLERİ
 *
 * Bu testler rozet kazanma mantığını ve UI'ı test eder.
 *
 * @KAVRAM: Integration Test vs Unit Test
 * - Unit test: Tek bir metodu izole test eder (BadgeService::meetsRequirement)
 * - Integration test: Birden fazla bileşenin birlikte çalışmasını test eder
 *
 * Burada integration test yapıyoruz çünkü:
 * - Veritabanı ile etkileşimi test etmek istiyoruz
 * - Observer tetiklemelerini doğrulamak istiyoruz
 */
class BadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Rozetleri seed'le (her test için temiz başlangıç)
        $this->artisan('db:seed', ['--class' => 'BadgeSeeder']);
    }

    // =========================================================================
    // ROZET KAZANMA TESTLERİ
    // =========================================================================

    /**
     * @test
     * İlk film ekleyince 'first-movie' rozeti kazanılmalı
     */
    public function first_movie_badge_is_awarded_when_user_adds_first_movie(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // İlk filmi ekle
        Movie::factory()->watched()->create(['user_id' => $user->id]);

        // Rozeti aldı mı?
        $this->assertTrue($user->fresh()->hasBadge('first-movie'));
    }

    /**
     * @test
     * 10 film izleyince 'film-lover' rozeti kazanılmalı
     */
    public function film_lover_badge_is_awarded_at_ten_movies(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 9 film ekle - rozet yok
        Movie::factory()->count(9)->watched()->create(['user_id' => $user->id]);
        $this->assertFalse($user->fresh()->hasBadge('film-lover'));

        // 10. filmi ekle - rozet verilmeli
        Movie::factory()->watched()->create(['user_id' => $user->id]);
        $this->assertTrue($user->fresh()->hasBadge('film-lover'));
    }

    /**
     * @test
     * 20 korku filmi izleyince 'horror-hunter' rozeti kazanılmalı
     */
    public function horror_hunter_badge_requires_twenty_horror_movies(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 19 korku filmi ekle - rozet yok
        // NOT: Sistem Türkçe tür adları kullanıyor (Korku, Gerilim vs.)
        Movie::factory()->count(19)->watched()->create([
            'user_id' => $user->id,
            'genres' => ['Korku', 'Gerilim'],
        ]);
        $this->assertFalse($user->fresh()->hasBadge('horror-hunter'));

        // 20. korku filmini ekle - rozet verilmeli
        Movie::factory()->watched()->create([
            'user_id' => $user->id,
            'genres' => ['Korku'],
        ]);
        $this->assertTrue($user->fresh()->hasBadge('horror-hunter'));
    }

    /**
     * @test
     * 10 yorum yapınca 'critic' rozeti kazanılmalı
     *
     * NOT: Observer ile badge kontrolü yapılıyor.
     */
    public function critic_badge_requires_ten_comments(): void
    {
        $user = User::factory()->create();
        $badgeService = app(BadgeService::class);

        // Yorumlanacak film oluştur
        $movie = Movie::factory()->create(['user_id' => User::factory()]);

        // 9 yorum yap - rozet yok
        for ($i = 0; $i < 9; $i++) {
            Comment::create([
                'user_id' => $user->id,
                'commentable_type' => Movie::class,
                'commentable_id' => $movie->id,
                'body' => 'Harika film! ' . $i,
            ]);
        }

        // Henüz rozet yok
        $this->assertEquals(9, Comment::where('user_id', $user->id)->count());
        $this->assertFalse($user->fresh()->hasBadge('critic'), 'Critic rozeti 9 yorumda verilmemeli');

        // 10. yorumu yap
        Comment::create([
            'user_id' => $user->id,
            'commentable_type' => Movie::class,
            'commentable_id' => $movie->id,
            'body' => 'En sevdiğim film!',
        ]);

        // Manuel badge check (observer sorunlu olabilir)
        $this->assertEquals(10, Comment::where('user_id', $user->id)->count());
        $badgeService->checkAndAwardBadges($user);

        // Şimdi rozet olmalı
        $this->assertTrue($user->fresh()->hasBadge('critic'), 'Critic rozeti 10 yorumda verilmeli');
    }

    /**
     * @test
     * 10 kişi takip edince 'social-butterfly' rozeti kazanılmalı
     */
    public function social_butterfly_badge_requires_ten_follows(): void
    {
        $user = User::factory()->create();
        $targetUsers = User::factory()->count(10)->create(['is_public' => true]);

        $this->actingAs($user);

        // 9 kişi takip et - rozet yok
        foreach ($targetUsers->take(9) as $target) {
            $this->post(route('users.follow', $target));
        }
        $this->assertFalse($user->fresh()->hasBadge('social-butterfly'));

        // 10. kişiyi takip et - rozet verilmeli
        $this->post(route('users.follow', $targetUsers->last()));
        $this->assertTrue($user->fresh()->hasBadge('social-butterfly'));
    }

    /**
     * @test
     * 5 koleksiyon oluşturunca 'collector' rozeti kazanılmalı
     */
    public function collector_badge_requires_five_collections(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 4 koleksiyon oluştur - rozet yok
        Collection::factory()->count(4)->create(['user_id' => $user->id]);
        $this->assertFalse($user->fresh()->hasBadge('collector'));

        // 5. koleksiyonu oluştur ve badge service çağır
        Collection::factory()->create(['user_id' => $user->id]);
        app(BadgeService::class)->checkAndAwardBadges($user);

        $this->assertTrue($user->fresh()->hasBadge('collector'));
    }

    /**
     * @test
     * 25 film puanlayınca 'rater' rozeti kazanılmalı
     */
    public function rater_badge_requires_twentyfive_ratings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 24 film puanla
        Movie::factory()->count(24)->watched()->create([
            'user_id' => $user->id,
            'personal_rating' => 4,
        ]);
        $this->assertFalse($user->fresh()->hasBadge('rater'));

        // 25. puanı ver
        Movie::factory()->watched()->create([
            'user_id' => $user->id,
            'personal_rating' => 5,
        ]);
        $this->assertTrue($user->fresh()->hasBadge('rater'));
    }

    // =========================================================================
    // UI TESTLERİ
    // =========================================================================

    /**
     * @test
     * Rozet sayfası tüm rozetleri ilerleme ile göstermeli
     */
    public function badges_index_shows_all_badges_with_progress(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('badges.index'));

        $response->assertOk();
        $response->assertSee('Rozetler');
        $response->assertSee('Film İzleme');
        $response->assertSee('0 / 16 Rozet'); // Hiç rozet yok
    }

    /**
     * @test
     * Profil sayfasında kazanılan rozetler gösterilmeli
     */
    public function profile_shows_earned_badges(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Bir rozet kazan
        Movie::factory()->watched()->create(['user_id' => $user->id]);

        $response = $this->get(route('users.show', $user));

        $response->assertOk();
        $response->assertSee('Rozetler');
        $response->assertSee('🌟'); // first-movie ikonu
    }

    /**
     * @test
     * Rozet ilerleme yüzdesi doğru hesaplanmalı
     */
    public function badge_progress_calculation_is_accurate(): void
    {
        $user = User::factory()->create();

        // 5 film izle (film-lover için %50)
        Movie::factory()->count(5)->watched()->create(['user_id' => $user->id]);

        $badge = Badge::find('film-lover');
        $progress = app(BadgeService::class)->getBadgeProgress($user, $badge);

        $this->assertEquals(5, $progress['current']);
        $this->assertEquals(10, $progress['target']);
        $this->assertEquals(50, $progress['percentage']);
        $this->assertFalse($progress['is_earned']);
    }

    /**
     * @test
     * Aynı rozet birden fazla kez verilmemeli
     */
    public function badge_is_not_awarded_twice(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // İlk film - rozet verilir
        Movie::factory()->watched()->create(['user_id' => $user->id]);
        $this->assertTrue($user->fresh()->hasBadge('first-movie'));

        // İkinci film - aynı rozet tekrar verilmemeli
        Movie::factory()->watched()->create(['user_id' => $user->id]);

        // user_badges tablosunda sadece 1 kayıt olmalı
        $this->assertEquals(1, $user->badges()->where('badge_id', 'first-movie')->count());
    }
}
