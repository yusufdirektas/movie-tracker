<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchlistControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_watchlist_shows_only_authenticated_users_unwatched_movies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Benim Izlenecek Filmim',
            'is_watched' => false,
        ]);
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Benim Izledigim Filmim',
            'is_watched' => true,
        ]);
        Movie::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Baskasinin Izlenecegi',
            'is_watched' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.watchlist'));

        $response->assertOk();
        $response->assertSee('Benim Izlenecek Filmim');
        $response->assertDontSee('Benim Izledigim Filmim');
        $response->assertDontSee('Baskasinin Izlenecegi');
    }

    public function test_watchlist_supports_search_and_genre_filters(): void
    {
        $user = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Matrix',
            'genres' => ['Bilim Kurgu'],
            'is_watched' => false,
        ]);
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Godfather',
            'genres' => ['Dram'],
            'is_watched' => false,
        ]);

        $searchResponse = $this
            ->actingAs($user)
            ->get(route('movies.watchlist', ['search' => 'mat']));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Matrix');
        $searchResponse->assertDontSee('Godfather');

        $genreResponse = $this
            ->actingAs($user)
            ->get(route('movies.watchlist', ['genre' => 'Dram']));

        $genreResponse->assertOk();
        $genreResponse->assertSee('Godfather');
        $genreResponse->assertDontSee('Matrix');
    }

    public function test_watchlist_title_sort_returns_alphabetical_order(): void
    {
        $user = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Zeta Film',
            'is_watched' => false,
        ]);
        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Alpha Film',
            'is_watched' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.watchlist', ['sort' => 'title']));

        $response->assertOk();
        $response->assertSeeInOrder(['Alpha Film', 'Zeta Film']);
    }

    public function test_watchlist_ajax_request_returns_partial_grid_only(): void
    {
        $user = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Ajax Watchlist Filmi',
            'is_watched' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.watchlist'), [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $response->assertSee('Ajax Watchlist Filmi');
        $response->assertDontSee('İzleme <span class="text-indigo-500">Listem</span>', false);
    }
}

