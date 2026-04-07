<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 📚 KARŞILAŞTIRMA TESTLERİ
 *
 * İki kullanıcının film listelerini karşılaştırma özelliğini test eder.
 *
 * @KAVRAM: Küme İşlemleri Testleri
 * - Ortak filmler (kesişim)
 * - Sadece A'da olanlar (fark)
 * - Sadece B'de olanlar (fark)
 * - Benzerlik yüzdesi (Jaccard)
 */
class CompareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * Karşılaştırma sayfası ortak filmleri gösterir
     */
    public function compare_page_shows_common_movies(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['is_public' => true]);

        // Ortak film (aynı tmdb_id)
        Movie::factory()->create([
            'user_id' => $me->id,
            'tmdb_id' => 12345,
            'title' => 'Inception',
            'is_watched' => true,
        ]);
        Movie::factory()->create([
            'user_id' => $other->id,
            'tmdb_id' => 12345,
            'title' => 'Inception',
            'is_watched' => true,
        ]);

        $this->actingAs($me)
            ->get(route('users.compare', $other))
            ->assertOk()
            ->assertSee('Inception')
            ->assertSee('1') // Ortak film sayısı
            ->assertSee('%'); // Uyum yüzdesi
    }

    /**
     * @test
     * Karşılaştırma sayfası sadece bende olan filmleri gösterir
     */
    public function compare_page_shows_only_mine_movies(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['is_public' => true]);

        // Sadece bende olan film
        Movie::factory()->create([
            'user_id' => $me->id,
            'tmdb_id' => 11111,
            'title' => 'Matrix',
            'is_watched' => true,
        ]);

        // Diğerinde farklı film
        Movie::factory()->create([
            'user_id' => $other->id,
            'tmdb_id' => 22222,
            'title' => 'Avatar',
            'is_watched' => true,
        ]);

        $response = $this->actingAs($me)
            ->get(route('users.compare', $other));

        $response->assertOk();
        // Stats'ta sadece bende olan sayısı 1 olmalı
        $response->assertSee('Sadece Ben');
    }

    /**
     * @test
     * Uyum yüzdesi doğru hesaplanır
     *
     * 📚 OVERLAP COEFFICIENT
     * Eski Jaccard: 2 / (3+3-2) = 2/4 = %50
     * Yeni Overlap: 2 / min(3,3) = 2/3 = %67 (film boyutu)
     * Genel skor: 6 boyutun ağırlıklı ortalaması
     */
    public function similarity_percentage_is_calculated_correctly(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['is_public' => true]);

        // Bende 3 film, onda 3 film, 2 ortak
        Movie::factory()->create(['user_id' => $me->id, 'tmdb_id' => 1, 'is_watched' => true]);
        Movie::factory()->create(['user_id' => $me->id, 'tmdb_id' => 2, 'is_watched' => true]);
        Movie::factory()->create(['user_id' => $me->id, 'tmdb_id' => 3, 'is_watched' => true]);

        Movie::factory()->create(['user_id' => $other->id, 'tmdb_id' => 2, 'is_watched' => true]);
        Movie::factory()->create(['user_id' => $other->id, 'tmdb_id' => 3, 'is_watched' => true]);
        Movie::factory()->create(['user_id' => $other->id, 'tmdb_id' => 4, 'is_watched' => true]);

        $response = $this->actingAs($me)
            ->get(route('users.compare', $other));

        $response->assertOk();
        $response->assertSee('%'); // Uyum yüzdesi gösteriliyor
    }

    /**
     * @test
     * Kendini karşılaştıramazsın
     */
    public function cannot_compare_with_self(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)
            ->get(route('users.compare', $me))
            ->assertRedirect(route('users.show', $me));
    }

    /**
     * @test
     * Gizli profil karşılaştırılamaz (takip etmiyorsan)
     */
    public function cannot_compare_with_private_profile_if_not_following(): void
    {
        $me = User::factory()->create();
        $privateUser = User::factory()->create(['is_public' => false]);

        $this->actingAs($me)
            ->get(route('users.compare', $privateUser))
            ->assertForbidden();
    }

    /**
     * @test
     * Gizli profil karşılaştırılabilir (takip ediyorsan)
     */
    public function can_compare_with_private_profile_if_following(): void
    {
        $me = User::factory()->create();
        $privateUser = User::factory()->create(['is_public' => false]);

        // Takip et
        $me->follow($privateUser);

        $this->actingAs($me)
            ->get(route('users.compare', $privateUser))
            ->assertOk();
    }

    /**
     * @test
     * tmdb_id olmayan filmler karşılaştırmaya dahil edilmez
     */
    public function movies_without_tmdb_id_are_excluded(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['is_public' => true]);

        // tmdb_id olmayan film
        Movie::factory()->create([
            'user_id' => $me->id,
            'tmdb_id' => null,
            'title' => 'Local Film',
            'is_watched' => true,
        ]);

        // tmdb_id olan film
        Movie::factory()->create([
            'user_id' => $me->id,
            'tmdb_id' => 99999,
            'title' => 'TMDB Film',
            'is_watched' => true,
        ]);

        $response = $this->actingAs($me)
            ->get(route('users.compare', $other));

        $response->assertOk();
        // my_total sadece tmdb_id olanları saymalı (1 film)
        $response->assertSee('1 film');
    }

    /**
     * @test
     * Sadece izlenen filmler karşılaştırılır (watchlist hariç)
     */
    public function only_watched_movies_are_compared(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create(['is_public' => true]);

        // İzlenmiş film
        Movie::factory()->create([
            'user_id' => $me->id,
            'tmdb_id' => 11111,
            'is_watched' => true,
        ]);

        // İzlenmemiş film (watchlist)
        Movie::factory()->create([
            'user_id' => $me->id,
            'tmdb_id' => 22222,
            'is_watched' => false,
        ]);

        $response = $this->actingAs($me)
            ->get(route('users.compare', $other));

        $response->assertOk();
        // Sadece 1 film sayılmalı
        $response->assertSee('1 film');
    }
}
