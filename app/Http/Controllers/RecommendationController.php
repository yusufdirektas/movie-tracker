<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TmdbService;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RecommendationController extends Controller
{
    protected $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $lastMovie = $user->movies()->latest()->first();
        $recommendations = [];

        if ($lastMovie && $lastMovie->tmdb_id) {

            // TMDB'den gelen önerileri o filme özel 24 saat hafızada tutuyoruz
            // CacheKeys ile tutarlı isimlendirme: user:{id}:recommendations:{movieId}:v1
            $results = Cache::remember(
                CacheKeys::recommendations($user->id, $lastMovie->tmdb_id),
                CacheKeys::TTL_LONG,
                function () use ($lastMovie) {
                    $response = $this->tmdb->getRecommendations($lastMovie->tmdb_id);
                    $res = $response?->successful() ? ($response->json()['results'] ?? []) : [];

                    if (empty($res)) {
                        $fallbackResponse = $this->tmdb->getSimilar($lastMovie->tmdb_id);
                        $res = $fallbackResponse?->successful() ? ($fallbackResponse->json()['results'] ?? []) : [];
                    }
                    return $res;
                }
            );

            if (!empty($results)) {
                $myMovieIds = $user->movies()->pluck('tmdb_id')->toArray();
                $recommendations = collect($results)
                    ->whereNotIn('id', $myMovieIds)
                    ->shuffle()
                    ->take(12);
            }
        }

        return view('movies.recommendations', compact('recommendations', 'lastMovie'));
    }
}
