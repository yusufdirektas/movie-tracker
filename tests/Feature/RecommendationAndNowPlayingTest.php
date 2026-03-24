<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationAndNowPlayingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tmdb.token' => 'dummy-test-token']);
        Cache::flush();
    }

    public function test_recommendations_page_excludes_movies_already_in_user_archive(): void
    {
        Http::fake([
            'api.themoviedb.org/*/recommendations*' => Http::response([
                'results' => [
                    ['id' => 999, 'title' => 'Arsivde Zaten Var'],
                    ['id' => 1000, 'title' => 'Yeni Oneri'],
                ],
            ], 200),
            'api.themoviedb.org/*' => Http::response(['results' => []], 200),
        ]);

        $user = User::factory()->create();
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Referans Film',
            'tmdb_id' => 550,
            'is_watched' => true,
        ]);
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Arsivde Zaten Var',
            'tmdb_id' => 999,
            'is_watched' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.recommendations'));

        $response->assertOk();
        $response->assertDontSee('Arsivde Zaten Var');
        $response->assertSee('Yeni Oneri');
    }

    public function test_recommendations_page_falls_back_to_similar_when_recommendations_empty(): void
    {
        Http::fake([
            'api.themoviedb.org/*/recommendations*' => Http::response(['results' => []], 200),
            'api.themoviedb.org/*/similar*' => Http::response([
                'results' => [
                    ['id' => 2001, 'title' => 'Benzer Film'],
                ],
            ], 200),
            'api.themoviedb.org/*' => Http::response(['results' => []], 200),
        ]);

        $user = User::factory()->create();
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Son Film',
            'tmdb_id' => 777,
            'is_watched' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.recommendations'));

        $response->assertOk();
        $response->assertSee('Benzer Film');
    }

    public function test_now_playing_page_excludes_movies_already_in_user_archive(): void
    {
        Http::fake([
            'api.themoviedb.org/*/now_playing*' => Http::response([
                'results' => [
                    ['id' => 111, 'title' => 'Arsivde Olan Vizyon Filmi'],
                    ['id' => 222, 'title' => 'Yeni Vizyon Filmi'],
                ],
            ], 200),
            'api.themoviedb.org/*' => Http::response(['results' => []], 200),
        ]);

        $user = User::factory()->create();
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Arsivde Olan Vizyon Filmi',
            'tmdb_id' => 111,
            'is_watched' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.now_playing'));

        $response->assertOk();
        $response->assertDontSee('Arsivde Olan Vizyon Filmi');
        $response->assertSee('Yeni Vizyon Filmi');
    }
}
