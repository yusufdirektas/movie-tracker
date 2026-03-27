<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_index_shows_only_public_users_and_excludes_self(): void
    {
        $currentUser = User::factory()->create(['name' => 'Current User', 'is_public' => true]);
        $publicUser = User::factory()->create(['name' => 'Public User', 'is_public' => true]);
        $privateUser = User::factory()->create(['name' => 'Private User', 'is_public' => false]);

        $response = $this
            ->actingAs($currentUser)
            ->get(route('users.index'));

        $response->assertOk();

        $users = $response->viewData('users')->getCollection();
        $this->assertTrue($users->contains('id', $publicUser->id));
        $this->assertFalse($users->contains('id', $privateUser->id));
        $this->assertFalse($users->contains('id', $currentUser->id));
    }

    public function test_users_index_search_filters_public_users_by_name_or_email(): void
    {
        $currentUser = User::factory()->create(['is_public' => true]);
        User::factory()->create(['name' => 'Ali Veli', 'email' => 'ali@example.com', 'is_public' => true]);
        User::factory()->create(['name' => 'Ayse Demo', 'email' => 'ayse@example.com', 'is_public' => true]);

        $response = $this
            ->actingAs($currentUser)
            ->get(route('users.index', ['search' => 'Ali']));

        $response->assertOk();
        $response->assertSee('Ali Veli');
        $response->assertDontSee('Ayse Demo');
    }

    public function test_private_profile_is_forbidden_for_non_follower(): void
    {
        $viewer = User::factory()->create();
        $privateOwner = User::factory()->create(['is_public' => false]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('users.show', $privateOwner));

        $response->assertForbidden();
    }

    public function test_private_profile_is_accessible_for_follower(): void
    {
        $viewer = User::factory()->create();
        $privateOwner = User::factory()->create(['is_public' => false]);

        $viewer->follow($privateOwner);

        $response = $this
            ->actingAs($viewer)
            ->get(route('users.show', $privateOwner));

        $response->assertOk();
    }

    public function test_feed_shows_only_watched_movies_of_followed_users(): void
    {
        $currentUser = User::factory()->create();
        $followedUser = User::factory()->create();
        $notFollowedUser = User::factory()->create();

        $currentUser->follow($followedUser);

        Movie::factory()->create([
            'user_id' => $followedUser->id,
            'title' => 'Takip Ettigim Izlenen Film',
            'is_watched' => true,
            'watched_at' => now(),
        ]);

        Movie::factory()->create([
            'user_id' => $followedUser->id,
            'title' => 'Takip Ettigim Izlenmeyen Film',
            'is_watched' => false,
            'watched_at' => null,
        ]);

        Movie::factory()->create([
            'user_id' => $notFollowedUser->id,
            'title' => 'Takip Etmedigim Film',
            'is_watched' => true,
            'watched_at' => now(),
        ]);

        $response = $this
            ->actingAs($currentUser)
            ->get(route('feed'));

        $response->assertOk();
        $response->assertSee('Takip Ettigim Izlenen Film');
        $response->assertDontSee('Takip Ettigim Izlenmeyen Film');
        $response->assertDontSee('Takip Etmedigim Film');
    }

    public function test_public_profile_shows_recent_activities_card_when_enabled(): void
    {
        $viewer = User::factory()->create();
        $profileOwner = User::factory()->create([
            'is_public' => true,
            'show_recent_activities' => true,
        ]);

        Movie::factory()->create([
            'user_id' => $profileOwner->id,
            'title' => 'Profil Aktivite Filmi',
            'is_watched' => true,
            'watched_at' => now(),
        ]);

        Collection::query()->create([
            'user_id' => $profileOwner->id,
            'name' => 'Profil Aktivite Koleksiyon',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => true,
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('users.show', $profileOwner));

        $response->assertOk();
        $response->assertSee('data-testid="recent-activities-card"', false);
        $response->assertSee('Profil Aktivite Filmi');
        $response->assertSee('Profil Aktivite Koleksiyon');
    }

    public function test_public_profile_hides_recent_activities_card_when_disabled(): void
    {
        $viewer = User::factory()->create();
        $profileOwner = User::factory()->create([
            'is_public' => true,
            'show_recent_activities' => false,
        ]);

        Movie::factory()->create([
            'user_id' => $profileOwner->id,
            'title' => 'Gizli Aktivite Filmi',
            'is_watched' => true,
            'watched_at' => now(),
        ]);

        $response = $this
            ->actingAs($viewer)
            ->get(route('users.show', $profileOwner));

        $response->assertOk();
        $response->assertDontSee('data-testid="recent-activities-card"', false);
    }
}
