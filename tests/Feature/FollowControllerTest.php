<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_follow_another_user(): void
    {
        $follower = User::factory()->create();
        $target = User::factory()->create();

        $response = $this
            ->actingAs($follower)
            ->post(route('users.follow', $target));

        $response->assertRedirect();
        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $target->id,
        ]);
    }

    public function test_user_cannot_follow_self(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('users.follow', $user));

        $response->assertRedirect();
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $user->id,
            'following_id' => $user->id,
        ]);
    }

    public function test_following_same_user_twice_creates_single_pivot_record(): void
    {
        $follower = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($follower)->post(route('users.follow', $target));
        $this->actingAs($follower)->post(route('users.follow', $target));

        $this->assertDatabaseCount('follows', 1);
    }

    public function test_user_can_unfollow_followed_user(): void
    {
        $follower = User::factory()->create();
        $target = User::factory()->create();
        $follower->follow($target);

        $response = $this
            ->actingAs($follower)
            ->delete(route('users.unfollow', $target));

        $response->assertRedirect();
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'following_id' => $target->id,
        ]);
    }

    public function test_user_followers_and_following_pages_are_accessible_when_authenticated(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $user->follow($other);

        $followersResponse = $this
            ->actingAs($user)
            ->get(route('users.followers', $other));

        $followingResponse = $this
            ->actingAs($user)
            ->get(route('users.following', $user));

        $followersResponse->assertOk();
        $followingResponse->assertOk();
    }
}
