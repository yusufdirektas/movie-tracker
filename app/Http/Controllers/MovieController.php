<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\User;
use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\UpdateMovieRequest;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MovieController extends Controller
{
    protected $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    // 1. ODA: SADECE İZLENENLER (Film Arşivim)
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // İSTATİSTİKLERİ SAYFALANDIRMADAN BAĞIMSIZ, TÜM VERİTABANINDAN ÇEKİYORUZ
        $totalMovies = $user->movies()->count();
        $baseWatchedQuery = $user->movies()->where('is_watched', true);

        $watchedCount = $baseWatchedQuery->count();
        $totalMinutes = $baseWatchedQuery->sum('runtime');
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        $highestRated = $baseWatchedQuery->orderByDesc('rating')->first();

        // FİLM LİSTELEME VE ARAMA SORGUSU
        $query = $user->movies()->where('is_watched', true)->orderBy('updated_at', 'desc');

        $filter = $request->input('filter', 'all');
        $search = mb_strtolower($request->input('search'), 'UTF-8');

        if ($filter === 'favorites') {
            $query->where('personal_rating', '>=', 4);
        }

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        // BÜYÜK DOKUNUŞ: get() yerine paginate(20) kullanıyoruz.
        // withQueryString() ise sayfa değiştirirken arama kelimesini kaybetmememizi sağlar.
        $movies = $query->paginate(20)->withQueryString();

        return view('movies.index', compact(
            'movies', 'search', 'filter', 'totalMovies', 'watchedCount',
            'totalHours', 'remainingMinutes', 'highestRated'
        ));
    }

    // 2. ODA: SADECE İZLENMEYECEKLER (İzleme Listem)
    public function watchlist(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $user->movies()->where('is_watched', false)->orderBy('updated_at', 'desc');
        $search = mb_strtolower($request->input('search'), 'UTF-8');

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Burada da 20'li sayfalandırma yapıyoruz
        $movies = $query->paginate(20)->withQueryString();
        $totalMovies = $user->movies()->where('is_watched', false)->count();

        return view('movies.watchlist', compact('movies', 'search', 'totalMovies'));
    }

    public function create()
    {
        return view('movies.create');
    }

    public function apiSearch(Request $request)
    {
        $query = mb_strtolower($request->input('query'), 'UTF-8');
        if (!$query) return response()->json([]);

        // Arama kısmı anlık olmalı, burayı cache'lemiyoruz.
        $response = $this->tmdb->searchMovies($query);

        if ($response->successful()) {
            return response()->json(collect($response->json()['results'])->take(6));
        }

        return response()->json([]);
    }

    public function store(StoreMovieRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $alreadyExists = $user->movies()->where('tmdb_id', $request->tmdb_id)->exists();

        if ($alreadyExists) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Bu film zaten arşivinde mevcut!'], 400);
            }
            return back()->with('error', 'Bu film zaten arşivinde mevcut!');
        }

        $response = $this->tmdb->getMovieDetails($request->tmdb_id);

        if ($response->successful()) {
            $movieData = $response->json();
            $director = collect($movieData['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';
            $isWatched = $request->boolean('is_watched');

            $user->movies()->create([
                'tmdb_id'      => $movieData['id'],
                'title'        => $movieData['title'],
                'director'     => $director,
                'poster_path'  => $movieData['poster_path'],
                'rating'       => $movieData['vote_average'],
                'runtime'      => $movieData['runtime'],
                'overview'     => empty($movieData['overview']) ? null : $movieData['overview'],
                'release_date' => $movieData['release_date'],
                'is_watched'   => $isWatched,
                'watched_at'   => $isWatched ? now() : null,
            ]);

            $message = $isWatched ? 'Film listeye eklendi!' : 'Film izleneceklere eklendi!';

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->route('movies.index')->with('success', $message);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Film bilgileri alınamadı.'], 500);
        }
        return back()->with('error', 'Film bilgileri alınamadı.');
    }

    public function show(Movie $movie)
    {
        $this->authorize('view', $movie);
        return redirect()->route('movies.index');
    }

    public function update(UpdateMovieRequest $request, Movie $movie)
    {
        $this->authorize('update', $movie);

        if ($request->has('personal_rating')) {
            $movie->update([
                'personal_rating' => $request->personal_rating
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Puan kaydedildi.']);
            }
            return back();
        }

        $newWatchedStatus = !$movie->is_watched;
        $movie->update([
            'is_watched' => $newWatchedStatus,
            'watched_at' => $newWatchedStatus ? ($movie->watched_at ?? now()) : null
        ]);

        return back()->with('success', 'Film durumu güncellendi.');
    }

    public function destroy(Movie $movie)
    {
        $this->authorize('delete', $movie);
        $movie->delete();
        return back()->with('success', 'Film silindi.');
    }

    public function import()
    {
        return view('movies.import');
    }

    // SİHİRLİ DOKUNUŞ 2: Öneriler sayfası artık TMDB'yi beklemeyecek!
    public function recommendations()
    {
        /** @var User $user */
        $user = Auth::user();
        $movies = $user->movies()->latest()->get();
        $lastMovie = $movies->first();
        $recommendations = [];

        if ($lastMovie && $lastMovie->tmdb_id) {

            // TMDB'den gelen önerileri o filme özel 24 saat (86400 saniye) hafızada tutuyoruz
            $cacheKey = 'movie_recommendations_' . $lastMovie->tmdb_id;

            $results = Cache::remember($cacheKey, now()->addHours(24), function () use ($lastMovie) {
                $response = $this->tmdb->getRecommendations($lastMovie->tmdb_id);
                $res = $response->successful() ? ($response->json()['results'] ?? []) : [];

                if (empty($res)) {
                    $fallbackResponse = $this->tmdb->getSimilar($lastMovie->tmdb_id);
                    $res = $fallbackResponse->successful() ? ($fallbackResponse->json()['results'] ?? []) : [];
                }
                return $res;
            });

            if (!empty($results)) {
                $myMovieIds = $movies->pluck('tmdb_id')->toArray();
                $recommendations = collect($results)
                    ->whereNotIn('id', $myMovieIds)
                    ->shuffle()
                    ->take(12);
            }
        }

        return view('movies.recommendations', compact('recommendations', 'lastMovie'));
    }

    // SİHİRLİ DOKUNUŞ 3: Vizyondakiler sayfası ışık hızında açılacak!
    public function nowPlaying()
    {
        /** @var User $user */
        $user = Auth::user();
        $myMovieIds = $user->movies()->pluck('tmdb_id')->toArray();
        $nowPlaying = [];

        // Vizyondaki filmleri her defasında çekmek yerine 12 saat hafızada tutuyoruz
        $results = Cache::remember('movies_now_playing', now()->addHours(12), function () {
            $response = $this->tmdb->getNowPlaying();
            return $response->successful() ? ($response->json()['results'] ?? []) : [];
        });

        if (!empty($results)) {
            $nowPlaying = collect($results)
                ->whereNotIn('id', $myMovieIds)
                ->take(12);
        }

        return view('movies.now_playing', compact('nowPlaying'));
    }
}
