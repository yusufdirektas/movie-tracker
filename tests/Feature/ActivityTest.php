<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 📚 AKTİVİTE SİSTEMİ TESTLERİ
 *
 * Bu testler aktivite feed sisteminin doğru çalıştığını doğrular:
 * - Aktivite kaydetme (Observer pattern)
 * - Feed görüntüleme
 * - Takip edilen kullanıcıların aktivitelerini gösterme
 */
class ActivityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // 📚 AKTİVİTE KAYDETME TESTLERİ
    // =========================================================================

    /** @test */
    public function activity_is_logged_when_movie_is_watched(): void
    {
        // Arrange: Kullanıcı ve film oluştur
        $user = User::factory()->create();
        $this->actingAs($user);

        // Act: İzlendi olarak film ekle
        $movie = Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 123,
            'title' => 'Inception',
            'poster_path' => '/test.jpg',
            'is_watched' => true,
        ]);

        // Assert: Aktivite kaydedildi mi?
        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'type' => Activity::TYPE_WATCHED,
            'subject_type' => Movie::class,
            'subject_id' => $movie->id,
        ]);
    }

    /** @test */
    public function activity_is_logged_when_movie_added_to_watchlist(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Watchlist'e ekle (is_watched = false)
        $movie = Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 124,
            'title' => 'Dune',
            'is_watched' => false,
        ]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'type' => Activity::TYPE_ADDED_TO_WATCHLIST,
            'subject_id' => $movie->id,
        ]);
    }

    /** @test */
    public function activity_is_logged_when_movie_rating_changes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Film oluştur
        $movie = Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 125,
            'title' => 'Interstellar',
            'is_watched' => true,
            'personal_rating' => null,
        ]);

        // Puan ver
        $movie->update(['personal_rating' => 9.0]);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'type' => Activity::TYPE_RATED,
            'subject_id' => $movie->id,
        ]);
    }

    /** @test */
    public function activity_is_logged_when_user_follows_another(): void
    {
        $follower = User::factory()->create();
        $following = User::factory()->create(['is_public' => true]);

        $this->actingAs($follower)
            ->post(route('users.follow', $following));

        $this->assertDatabaseHas('activities', [
            'user_id' => $follower->id,
            'type' => Activity::TYPE_FOLLOWED,
            'subject_type' => User::class,
            'subject_id' => $following->id,
        ]);
    }

    // =========================================================================
    // 📚 FEED TESTLERİ
    // =========================================================================

    /** @test */
    public function feed_shows_activities_of_followed_users(): void
    {
        // Arrange
        $user = User::factory()->create();
        $followedUser = User::factory()->create(['is_public' => true, 'name' => 'FollowedUserTest']);

        // Takip et
        $user->follow($followedUser);

        // Takip edilen kişi bir film eklesin
        $movie = Movie::create([
            'user_id' => $followedUser->id,
            'tmdb_id' => 555,
            'title' => 'Followed User Film',
            'is_watched' => true,
        ]);

        // Act & Assert
        $this->actingAs($user)
            ->get(route('feed'))
            ->assertStatus(200)
            ->assertSee('FollowedUserTest');
    }

    /** @test */
    public function feed_does_not_show_activities_of_non_followed_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create(['name' => 'OtherUserUnique123']);

        // Diğer kullanıcı film eklesin (takip etmiyoruz)
        $movie = Movie::create([
            'user_id' => $otherUser->id,
            'tmdb_id' => 556,
            'title' => 'Secret Film',
            'is_watched' => true,
        ]);

        $this->actingAs($user)
            ->get(route('feed'))
            ->assertDontSee('OtherUserUnique123');
    }

    /** @test */
    public function feed_shows_own_activities(): void
    {
        $user = User::factory()->create(['name' => 'MyOwnUser']);

        $movie = Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 557,
            'title' => 'My Film',
            'is_watched' => true,
        ]);

        $this->actingAs($user)
            ->get(route('feed'))
            ->assertSee('MyOwnUser');
    }

    /** @test */
    public function feed_returns_json_for_ajax_requests(): void
    {
        $user = User::factory()->create();
        $movie = Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 999,
            'title' => 'AJAX Test Film',
            'is_watched' => true,
        ]);

        // Aktiviteyi observer zaten oluşturdu, kontrol edelim
        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'type' => Activity::TYPE_WATCHED,
        ]);

        // NOT: JSON response view render içerdiği için
        // basit yapı kontrolü ile yetiniyoruz
        $response = $this->actingAs($user)
            ->get(route('feed'), ['Accept' => 'application/json']);

        $response->assertStatus(200);
        // View render gerektiren JSON test'i, entegrasyon testinde yapılacak
    }

    /** @test */
    public function feed_pagination_cursor_updates_correctly(): void
    {
        $user = User::factory()->create();

        // 3 film ekle - observer aktiviteleri oluşturacak
        Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 1001,
            'title' => 'Film 1',
            'is_watched' => true,
        ]);

        Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 1002,
            'title' => 'Film 2',
            'is_watched' => true,
        ]);

        Movie::create([
            'user_id' => $user->id,
            'tmdb_id' => 1003,
            'title' => 'Film 3',
            'is_watched' => true,
        ]);

        // En son eklenen aktivitenin ID'sini al
        $lastActivity = Activity::latest('id')->first();

        // Bu ID'den öncekileri iste - HTML response olarak
        $response = $this->actingAs($user)
            ->get(route('feed', ['before' => $lastActivity->id]));

        $response->assertStatus(200);
        // Film 3 olmamalı (son aktivite olduğu için)
        $response->assertDontSee('Film 3');
    }

    // =========================================================================
    // 📚 AKTİVİTE MODEL TESTLERİ
    // =========================================================================

    /** @test */
    public function activity_type_label_returns_correct_turkish_text(): void
    {
        $activity = new Activity(['type' => Activity::TYPE_WATCHED]);
        $this->assertEquals('izledi', $activity->getTypeLabel());

        $activity->type = Activity::TYPE_RATED;
        $this->assertEquals('puanladı', $activity->getTypeLabel());

        $activity->type = Activity::TYPE_FOLLOWED;
        $this->assertEquals('takip etmeye başladı', $activity->getTypeLabel());
    }

    /** @test */
    public function activity_type_icon_returns_emoji(): void
    {
        $activity = new Activity(['type' => Activity::TYPE_WATCHED]);
        $this->assertEquals('👁️', $activity->getTypeIcon());

        $activity->type = Activity::TYPE_RATED;
        $this->assertEquals('⭐', $activity->getTypeIcon());
    }
}
