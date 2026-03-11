<?php

namespace Tests\Unit;

use App\Models\Movie;
use App\Models\User;
use App\Policies\MoviePolicy;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MoviePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_movie()
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        
        $policy = new MoviePolicy();
        $this->assertTrue($policy->view($user, $movie));
    }

    public function test_user_cannot_view_others_movie()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user2->id]);
        
        $policy = new MoviePolicy();
        $this->assertFalse($policy->view($user1, $movie));
    }

    public function test_user_can_update_own_movie()
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        
        $policy = new MoviePolicy();
        $this->assertTrue($policy->update($user, $movie));
    }

    public function test_user_can_delete_own_movie()
    {
        $user = User::factory()->create();
        $movie = Movie::factory()->create(['user_id' => $user->id]);
        
        $policy = new MoviePolicy();
        $this->assertTrue($policy->delete($user, $movie));
    }
}
