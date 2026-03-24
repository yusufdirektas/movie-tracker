<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggling_collection_to_public_generates_share_token_when_missing(): void
    {
        $user = User::factory()->create();
        $collection = Collection::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Koleksiyon',
            'description' => null,
            'icon' => 'folder',
            'color' => '#6366f1',
            'is_public' => false,
            'share_token' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('privacy.collection.toggle', $collection));

        $response->assertRedirect();

        $collection->refresh();
        $this->assertTrue($collection->is_public);
        $this->assertNotNull($collection->share_token);
    }
}
