<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    /**
     * SADECE İZLENMEYECEKLER (İzleme Listem)
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $user->movies()->where('is_watched', false);
        $search = mb_strtolower($request->input('search'), 'UTF-8');
        $genre = $request->input('genre');

        $allowedSorts = [
            'updated_at'   => 'desc',
            'title'        => 'asc',
            'rating'       => 'desc',
            'release_date' => 'desc',
            'runtime'      => 'desc',
        ];
        $sort = $request->input('sort', 'updated_at');
        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'updated_at';
        }
        $query->orderBy($sort, $allowedSorts[$sort]);

        if ($genre) {
            $query->whereJsonContains('genres', $genre);
        }

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $movies = $query->paginate(20)->withQueryString();
        $totalMovies = $user->movies()->where('is_watched', false)->count();

        $availableGenres = $user->movies()
            ->where('is_watched', false)
            ->whereNotNull('genres')
            ->pluck('genres')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('movies.watchlist', compact('movies', 'search', 'genre', 'availableGenres', 'totalMovies', 'sort'));
    }
}
