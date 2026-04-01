<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TmdbService;
use App\Support\CacheKeys;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class NowPlayingController extends Controller
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
        $myMovieIds = $user->movies()->pluck('tmdb_id')->toArray();
        $nowPlaying = [];

        // Vizyondaki filmleri her defasında çekmek yerine 12 saat hafızada tutuyoruz
        // CacheKeys::nowPlaying() global key döndürür (tüm kullanıcılar için aynı)
        $results = Cache::remember(
            CacheKeys::nowPlaying(),
            CacheKeys::TTL_LONG / 2, // 12 saat
            function () {
                $response = $this->tmdb->getNowPlaying();
                return $response?->successful() ? ($response->json()['results'] ?? []) : [];
            }
        );

        if (!empty($results)) {
            $nowPlaying = collect($results)
                ->whereNotIn('id', $myMovieIds)
                ->take(12);
        }

        return view('movies.now_playing', compact('nowPlaying'));
    }
}
