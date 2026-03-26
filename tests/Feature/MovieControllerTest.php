<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Collection;
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

    public function test_movie_poster_component_includes_retry_ui_for_failed_images(): void
    {
        $view = $this->blade(
            '<x-movie-poster path="/poster.jpg" alt="Poster Test" />'
        );

        $view->assertSee('Poster yüklenemedi');
        $view->assertSee('Tekrar Dene');
        $view->assertSee('x-bind:src="imageSrc"', false);
    }

    public function test_movie_index_does_not_eager_load_collections_in_grid_query(): void
    {
        $user = User::factory()->create();
        Movie::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_watched' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.index'));

        $response->assertOk();

        $movies = $response->viewData('movies');
        $this->assertNotNull($movies);

        foreach ($movies as $movie) {
            $this->assertFalse($movie->relationLoaded('collections'));
        }
    }

    public function test_user_can_complete_create_rate_collection_and_delete_flow(): void
    {
        Http::fake([
            'api.themoviedb.org/*' => Http::response([
                'id' => 603,
                'title' => 'The Matrix',
                'poster_path' => '/matrix.jpg',
                'vote_average' => 8.2,
                'runtime' => 136,
                'overview' => 'Neo test',
                'release_date' => '1999-03-31',
                'genres' => [
                    ['id' => 28, 'name' => 'Aksiyon'],
                ],
                'credits' => [
                    'crew' => [
                        ['job' => 'Director', 'name' => 'Wachowski'],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Favoriler',
            'description' => null,
            'icon' => 'heart',
            'color' => '#ef4444',
            'is_public' => false,
        ]);

        $this->actingAs($user)->post(route('movies.store'), [
            'tmdb_id' => 603,
            'is_watched' => '1',
        ])->assertRedirect(route('movies.index'));

        $movie = Movie::query()
            ->where('user_id', $user->id)
            ->where('tmdb_id', 603)
            ->firstOrFail();

        $this->actingAs($user)->putJson(route('movies.update', $movie), [
            'personal_rating' => 5,
        ])->assertOk()->assertJson([
            'success' => true,
        ]);

        $this->actingAs($user)->postJson(route('collections.addMovie', $collection), [
            'movie_id' => $movie->id,
        ])->assertOk()->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('collection_movie', [
            'collection_id' => $collection->id,
            'movie_id' => $movie->id,
        ]);

        $this->actingAs($user)->delete(route('movies.destroy', $movie))
            ->assertRedirect(route('movies.index'));

        $this->assertDatabaseMissing('movies', [
            'id' => $movie->id,
        ]);
    }

    public function test_duplicate_movie_store_sets_actionable_error_flash_message(): void
    {
        $user = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'tmdb_id' => 777,
            'media_type' => 'movie',
        ]);

        $response = $this->actingAs($user)->from(route('movies.create'))->post(route('movies.store'), [
            'tmdb_id' => 777,
            'is_watched' => '1',
            'media_type' => 'movie',
        ]);

        $response->assertRedirect(route('movies.create'));
        $response->assertSessionHas('error', 'Bu içerik zaten arşivinde mevcut!');
        $response->assertSessionHas('error_action', 'Aramaya dönüp farklı bir içerik seçebilirsin.');
    }

    public function test_movie_create_page_uses_global_flash_instead_of_local_duplicate_alerts(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('movies.create'));

        $response->assertOk();
        $response->assertDontSee("session('success')", false);
        $response->assertDontSee("session('error')", false);
    }
}
