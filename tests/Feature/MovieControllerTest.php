<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MovieControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tmdb.token' => 'dummy-test-token']);
    }

    public function test_guest_is_redirected_to_login()
    {
        $response = $this->get(route('movies.index'));
        $response->assertRedirect('/login');
    }

    public function test_auth_user_can_view_movie_index()
    {
        $user = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Film 1',
            'is_watched' => true,
        ]);

        $response = $this->actingAs($user)->get(route('movies.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Film 1');
    }

    public function test_auth_user_can_view_watchlist()
    {
        $user = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Watchlist Film',
            'is_watched' => false,
        ]);

        $response = $this->actingAs($user)->get(route('movies.watchlist'));

        $response->assertStatus(200);
        $response->assertSee('Watchlist Film');
    }

    public function test_auth_user_can_store_movie()
    {
        // TMDB API çağrısını simüle et (Fake HTTP)
        Http::fake([
            'api.themoviedb.org/*' => Http::response([
                'id' => 550,
                'title' => 'Fight Club',
                'poster_path' => '/test.jpg',
                'vote_average' => 8.4,
                'runtime' => 139,
                'overview' => 'Test açıklama',
                'release_date' => '1999-10-15',
                'genres' => [
                    ['id' => 18, 'name' => 'Dram'],
                    ['id' => 53, 'name' => 'Gerilim'],
                ],
                'credits' => [
                    'crew' => [
                        ['job' => 'Director', 'name' => 'David Fincher'],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('movies.store'), [
            'tmdb_id' => 550,
            'is_watched' => '1',
        ]);

        $response->assertRedirect(route('movies.index'));
        $this->assertDatabaseHas('movies', [
            'user_id' => $user->id,
            'title' => 'Fight Club',
            'is_watched' => true,
        ]);
    }

    public function test_api_search_without_query_returns_empty_array_without_error()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('movies.api_search'));

        $response
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_movie_index_generates_missing_share_token_for_legacy_user_data(): void
    {
        $user = User::factory()->create([
            'share_token' => null,
            'is_public' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.index'));

        $response->assertOk();
        $user->refresh();
        $this->assertNotNull($user->share_token);
    }

    public function test_movie_index_contains_accessible_desktop_nav_items(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('movies.index'));

        $response->assertOk();
        $response->assertSee('data-nav-group', false);
        $response->assertSee('data-nav-item', false);
        $response->assertSee('aria-label="Film Arşivim"', false);
        $response->assertSee('@keydown.escape.window="closeMenu()"', false);
        $response->assertSee(':aria-hidden="(!open).toString()"', false);
        $response->assertSee('aria-label="İzleme Listem"', false);
        $response->assertSee('aria-label="Sana Özel Öneriler"', false);
        $response->assertSee('x-ref="menuToggle"', false);
        $response->assertSee('x-ref="firstMobileLink"', false);
        $response->assertSee("x-on:click=\"closeMenu()\"", false);
    }

    public function test_deleting_movie_redirects_to_movies_index_instead_of_back(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('movies.show', $movie))
            ->delete(route('movies.destroy', $movie));

        $response->assertRedirect(route('movies.index'));
        $this->assertDatabaseMissing('movies', ['id' => $movie->id]);
    }

    public function test_movie_show_renders_collection_dropdown_opening_upwards(): void
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.show', $movie));

        $response->assertOk();
        $response->assertSee('bottom-full mb-2 w-72', false);
    }
}
