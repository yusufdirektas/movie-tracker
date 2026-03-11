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
            'is_watched' => true
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
            'is_watched' => false
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
                    ]
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
}
