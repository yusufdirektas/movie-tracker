<?php

namespace Tests\Feature;

use App\Services\TmdbService;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Tests\TestCase;

class TmdbServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.tmdb.token' => 'dummy-test-token']);
    }

    public function test_search_movie_returns_successful_data_from_tmdb()
    {
        Http::fake([
            'api.themoviedb.org/*' => Http::response([
                'results' => [
                    ['id' => 123, 'title' => 'Inception Test']
                ]
            ], 200),
        ]);

        $service = new TmdbService();
        $response = $service->searchMovie('Inception');

        $this->assertTrue($response->successful());
        $this->assertEquals(123, $response->json()['results'][0]['id']);
        $this->assertEquals('Inception Test', $response->json()['results'][0]['title']);
    }

    public function test_tmdb_service_handles_connection_exceptions_safely()
    {
        Http::fake(function () {
             throw new ConnectionException('Simulative network failure directly to TMDB');
        });

        $service = new TmdbService();
        $response = $service->searchMovie('Network Failure Film');

        $this->assertNull($response); // Our implementation catches and returns null
    }

    public function test_tmdb_service_handles_timeout_gracefully()
    {
        Http::fake([
            'api.themoviedb.org/*' => Http::response(null, 500),
        ]);

        $service = new TmdbService();
        $response = $service->searchMovie('Server Error Film');
        
        $this->assertFalse($response->successful());
        $this->assertEquals(500, $response->status());
    }
}
