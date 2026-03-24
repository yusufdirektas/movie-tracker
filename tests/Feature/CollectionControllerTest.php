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
}
