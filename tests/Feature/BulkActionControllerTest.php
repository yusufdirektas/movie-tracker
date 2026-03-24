<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkActionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_delete_removes_only_authenticated_users_movies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownMovie = Movie::factory()->create(['user_id' => $user->id]);
        $othersMovie = Movie::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($user)
            ->delete(route('movies.bulk.delete'), [
                'movie_ids' => [$ownMovie->id, $othersMovie->id],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('movies', ['id' => $ownMovie->id]);
        $this->assertDatabaseHas('movies', ['id' => $othersMovie->id]);
    }

    public function test_bulk_mark_as_watched_updates_only_owned_movies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownMovie = Movie::factory()->create([
            'user_id' => $user->id,
            'is_watched' => false,
            'watched_at' => null,
        ]);
        $othersMovie = Movie::factory()->create([
            'user_id' => $otherUser->id,
            'is_watched' => false,
            'watched_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('movies.bulk.watched'), [
                'movie_ids' => [$ownMovie->id, $othersMovie->id],
            ]);

        $response->assertRedirect();

        $ownMovie->refresh();
        $othersMovie->refresh();

        $this->assertTrue($ownMovie->is_watched);
        $this->assertNotNull($ownMovie->watched_at);
        $this->assertFalse($othersMovie->is_watched);
        $this->assertNull($othersMovie->watched_at);
    }

    public function test_bulk_mark_as_unwatched_clears_watched_state_only_for_owner(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownMovie = Movie::factory()->create([
            'user_id' => $user->id,
            'is_watched' => true,
            'watched_at' => now(),
        ]);
        $othersMovie = Movie::factory()->create([
            'user_id' => $otherUser->id,
            'is_watched' => true,
            'watched_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('movies.bulk.unwatched'), [
                'movie_ids' => [$ownMovie->id, $othersMovie->id],
            ]);

        $response->assertRedirect();

        $ownMovie->refresh();
        $othersMovie->refresh();

        $this->assertFalse($ownMovie->is_watched);
        $this->assertNull($ownMovie->watched_at);
        $this->assertTrue($othersMovie->is_watched);
        $this->assertNotNull($othersMovie->watched_at);
    }

    public function test_bulk_add_to_collection_adds_only_owned_movies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Toplu Ekleme Koleksiyonu',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);

        $ownMovie = Movie::factory()->create(['user_id' => $user->id]);
        $othersMovie = Movie::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($user)
            ->post(route('movies.bulk.collection'), [
                'collection_id' => $collection->id,
                'movie_ids' => [$ownMovie->id, $othersMovie->id],
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('collection_movie', [
            'collection_id' => $collection->id,
            'movie_id' => $ownMovie->id,
        ]);
        $this->assertDatabaseMissing('collection_movie', [
            'collection_id' => $collection->id,
            'movie_id' => $othersMovie->id,
        ]);
    }

    public function test_bulk_add_to_collection_fails_for_collection_not_owned_by_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $othersCollection = Collection::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Baska Kullanici Koleksiyonu',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);

        $movie = Movie::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->post(route('movies.bulk.collection'), [
                'collection_id' => $othersCollection->id,
                'movie_ids' => [$movie->id],
            ]);

        $response->assertNotFound();
    }
}
