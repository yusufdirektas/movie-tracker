<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Collection;
use App\Models\Movie;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    /**
     * Herkese açık film arşivi (Profil)
     */
    public function showArchive($token)
    {
        $user = User::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        $movies = $user->movies()
            ->watched()
            ->orderBy('updated_at', 'desc')
            ->paginate(24);

        $totalMovies = $user->movies()->count();
        $watchedCount = $user->movies()->watched()->count();

        return view('public.archive', compact('user', 'movies', 'totalMovies', 'watchedCount'));
    }

    /**
     * Herkese açık koleksiyon
     */
    public function showCollection($token)
    {
        $collection = Collection::where('share_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        $user = $collection->user;
        $movies = $collection->movies()
            ->orderBy('collection_movie.created_at', 'desc')
            ->paginate(24);

        return view('public.collection', compact('collection', 'user', 'movies'));
    }
}
