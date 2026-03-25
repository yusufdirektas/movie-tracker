<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_user_can_view_own_collections_index(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Benim Koleksiyonum',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);

        Collection::query()->create([
            'user_id' => $otherUser->id,
            'name' => 'Baska Koleksiyon',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('collections.index'));

        $response->assertOk();
        $response->assertSee('Benim Koleksiyonum');
        $response->assertDontSee('Baska Koleksiyon');
    }

    public function test_user_cannot_view_another_users_collection(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Gizli Koleksiyon',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('collections.show', $collection));

        $response->assertForbidden();
    }

    public function test_user_can_create_collection_with_defaults(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('collections.store'), [
                'name' => 'Yeni Koleksiyon',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('collections', [
            'user_id' => $user->id,
            'name' => 'Yeni Koleksiyon',
            'icon' => 'folder',
            'color' => '#6366f1',
        ]);
    }

    public function test_user_cannot_add_another_users_movie_to_collection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Koleksiyon',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);

        $otherUsersMovie = Movie::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('collections.addMovie', $collection), [
                'movie_id' => $otherUsersMovie->id,
            ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('collection_movie', [
            'collection_id' => $collection->id,
            'movie_id' => $otherUsersMovie->id,
        ]);
    }

    public function test_user_can_add_movie_to_collection_with_json_response(): void
    {
        $user = User::factory()->create();
        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Aksiyon',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);
        $movie = Movie::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson(route('collections.addMovie', $collection), [
                'movie_id' => $movie->id,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'collection_id' => $collection->id,
                'movie_id' => $movie->id,
            ]);

        $this->assertDatabaseHas('collection_movie', [
            'collection_id' => $collection->id,
            'movie_id' => $movie->id,
        ]);
    }

    public function test_user_gets_conflict_json_when_movie_already_in_collection(): void
    {
        $user = User::factory()->create();
        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Drama',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);
        $movie = Movie::factory()->create([
            'user_id' => $user->id,
        ]);
        $collection->movies()->attach($movie->id);

        $response = $this
            ->actingAs($user)
            ->postJson(route('collections.addMovie', $collection), [
                'movie_id' => $movie->id,
            ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_user_cannot_add_movie_to_another_users_collection_with_json_request(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $collection = Collection::query()->create([
            'user_id' => $owner->id,
            'name' => 'Gizli',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);
        $movie = Movie::factory()->create([
            'user_id' => $viewer->id,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->postJson(route('collections.addMovie', $collection), [
                'movie_id' => $movie->id,
            ]);

        $response->assertForbidden();
    }

    public function test_duplicate_collection_add_sets_actionable_error_flash_message(): void
    {
        $user = User::factory()->create();
        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Tekrar Test',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
        ]);
        $movie = Movie::factory()->create([
            'user_id' => $user->id,
        ]);
        $collection->movies()->attach($movie->id);

        $response = $this
            ->actingAs($user)
            ->from(route('movies.show', $movie))
            ->post(route('collections.addMovie', $collection), [
                'movie_id' => $movie->id,
            ]);

        $response->assertRedirect(route('movies.show', $movie));
        $response->assertSessionHas('error', 'Bu film zaten bu koleksiyonda!');
        $response->assertSessionHas('error_action', 'Koleksiyon detayından mevcut filmleri kontrol edebilirsin.');
    }
}
