<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Movie;
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

        $search = mb_strtolower($request->input('search'), 'UTF-8');
        $genre = $request->input('genre');
        $sort = $request->input('sort', 'updated_at');

        $allowedSorts = [
            'updated_at'   => 'desc',
            'title'        => 'asc',
            'rating'       => 'desc',
            'release_date' => 'desc',
            'runtime'      => 'desc',
        ];

        // ---------------------------------------------------------------------
        // 📚 REFACTORING (KOD İYİLEŞTİRME)
        // MovieController'daki gibi burada da Local Scope'ları kullanıyoruz.
        // Aynı filtreleme mantığını iki kez yazmaktan kurtulduk (DRY Prensibi).
        // ---------------------------------------------------------------------

        /**
         * 📚 EAGER LOADING - with('collections')
         * View'da filmlerin hangi koleksiyonlarda olduğunu gösteriyorsak,
         * bu ilişkiyi önceden yüklemeliyiz. Aksi halde N+1 query problemi oluşur.
         */
        $query = $user->movies()
            ->with('collections')  // 🚀 EAGER LOADING
            ->unwatched()
            ->searchByTitle($search)
            ->filterByGenre($genre)
            ->applySort($sort, $allowedSorts);

        $movies = $query->paginate(20)->withQueryString();
        $totalMovies = $user->movies()->unwatched()->count();

        // Türleri static metodumuzla alıyoruz (isWatched = false)
        $availableGenres = Movie::getAvailableGenres($user->id, false);

        // Kullanıcının Koleksiyonları (Toplu işlem dropdown'ı için)
        $collections = $user->collections()->orderBy('name')->get();

        if ($request->ajax()) {
            return view('movies.partials._watchlist_grid', compact('movies'));
        }

        return view('movies.watchlist', compact('movies', 'search', 'genre', 'availableGenres', 'totalMovies', 'sort', 'collections'));
    }
}
