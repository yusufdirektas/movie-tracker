<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_export_includes_only_authenticated_users_watched_movies(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Benim Izledigim Film',
            'is_watched' => true,
            'watched_at' => now(),
            'tmdb_id' => 123,
            'genres' => ['Dram'],
        ]);

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'Benim Izlemedigim Film',
            'is_watched' => false,
            'watched_at' => null,
            'genres' => ['Komedi'],
        ]);

        Movie::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Baska Kullanici Filmi',
            'is_watched' => true,
            'watched_at' => now(),
            'genres' => ['Aksiyon'],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.export.csv'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Film Adı', $content);
        $this->assertStringContainsString('Yönetmen', $content);
        $this->assertStringContainsString('Türler', $content);
        $this->assertStringContainsString('TMDB Linki', $content);
        $this->assertStringContainsString('Benim Izledigim Film', $content);
        $this->assertStringNotContainsString('Benim Izlemedigim Film', $content);
        $this->assertStringNotContainsString('Baska Kullanici Filmi', $content);
    }

    public function test_json_export_returns_expected_structure_for_watched_movies_only(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Movie::factory()->create([
            'user_id' => $user->id,
            'title' => 'JSON Film',
            'is_watched' => true,
            'watched_at' => now()->subDay(),
            'tmdb_id' => 456,
            'genres' => ['Bilim Kurgu'],
        ]);

        Movie::factory()->create([
            'user_id' => $otherUser->id,
            'title' => 'Diger Kullanici JSON Film',
            'is_watched' => true,
            'watched_at' => now(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('movies.export.json'));

        $response->assertOk();
        $response->assertHeader('Content-Disposition');

        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertSame('JSON Film', $data[0]['title']);
        $this->assertArrayHasKey('director', $data[0]);
        $this->assertArrayHasKey('genres', $data[0]);
        $this->assertArrayHasKey('release_date', $data[0]);
        $this->assertArrayHasKey('runtime_minutes', $data[0]);
        $this->assertArrayHasKey('tmdb_rating', $data[0]);
        $this->assertArrayHasKey('personal_rating', $data[0]);
        $this->assertArrayHasKey('watched_at', $data[0]);
        $this->assertArrayHasKey('tmdb_id', $data[0]);
    }
}
