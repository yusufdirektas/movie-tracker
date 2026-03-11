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
        $query = $user->movies()->where('is_watched', true);

        $filter = $request->input('filter', 'all');
        $search = mb_strtolower($request->input('search'), 'UTF-8');
        $genre = $request->input('genre');

        /**
         * 📚 SIRALAMA WHİTELİST GÜVENLİĞİ:
         *
         * Kullanıcıdan gelen 'sort' değerini DİREKT olarak orderBy'a vermek
         * SQL Injection riski taşır. Örneğin kötü niyetli biri:
         *   ?sort=title; DROP TABLE movies;
         * gibi bir şey gönderebilir.
         *
         * ÇÖZÜM: İzin verilen sıralama seçeneklerini bir whitelist'te tutuyoruz.
         * Kullanıcının gönderdiği değer listede yoksa varsayılan sıralamayı kullanıyoruz.
         */
        $allowedSorts = [
            'updated_at'      => 'desc',   // Son eklenen (varsayılan)
            'title'           => 'asc',    // İsme göre A-Z
            'rating'          => 'desc',   // TMDB puanına göre
            'personal_rating' => 'desc',   // Kişisel puana göre
            'release_date'    => 'desc',   // Yayın tarihine göre
            'runtime'         => 'desc',   // Süreye göre
        ];

        $sort = $request->input('sort', 'updated_at');
        // Whitelist'te yoksa varsayılana dön
        if (!array_key_exists($sort, $allowedSorts)) {
            $sort = 'updated_at';
        }
        $query->orderBy($sort, $allowedSorts[$sort]);

        if ($filter === 'favorites') {
            $query->where('personal_rating', '>=', 4);
        }

        if ($genre) {
            $query->whereJsonContains('genres', $genre);
        }

        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $movies = $query->paginate(20)->withQueryString();

        $availableGenres = $user->movies()
            ->where('is_watched', true)
            ->whereNotNull('genres')
            ->pluck('genres')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        return view('movies.index', compact(
            'movies', 'search', 'filter', 'genre', 'availableGenres', 'sort',
            'totalMovies', 'watchedCount', 'totalHours', 'remainingMinutes', 'highestRated'
        ));
    }

    // 2. ODA: SADECE İZLENMEYECEKLER (İzleme Listem)
    public function watchlist(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $user->movies()->where('is_watched', false);
        $search = mb_strtolower($request->input('search'), 'UTF-8');
        $genre = $request->input('genre');

        // Aynı whitelist yaklaşımı (güvenlik için)
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

    public function create()
    {
        return view('movies.create');
    }

    public function apiSearch(Request $request)
    {
        $query = mb_strtolower($request->input('query'), 'UTF-8');
        if (!$query) return response()->json([]);

        // Arama kısmı anlık olmalı, burayı cache'lemiyoruz.
        /**
         * 📚 NULL-SAFE OPERATOR (?->):
         * TmdbService artık hata durumunda null dönebiliyor.
         * $response->successful() yerine $response?->successful() kullanıyoruz.
         * Eğer $response null ise, ?-> otomatik olarak false döner
         * ve uygulama çökmez.
         */
        $response = $this->tmdb->searchMovies($query);

        if ($response?->successful()) {
            return response()->json(collect($response->json()['results'] ?? [])->take(6));
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

        if ($response?->successful()) {
            $movieData = $response->json();
            $director = collect($movieData['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';
            $isWatched = $request->boolean('is_watched');

            // TMDB'den gelen genre objeleri: [{"id":28,"name":"Aksiyon"}, ...]
            // Sadece isimleri alıyoruz: ["Aksiyon", "Bilim Kurgu", "Gerilim"]
            $genres = collect($movieData['genres'] ?? [])->pluck('name')->toArray();

            $user->movies()->create([
                'tmdb_id'      => $movieData['id'],
                'title'        => $movieData['title'],
                'director'     => $director,
                'genres'       => $genres,
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

    /**
     * 📚 ROUTE MODEL BINDING:
     * URL'de /movies/5 yazıldığında Laravel otomatik olarak
     * Movie::findOrFail(5) çalıştırır ve $movie değişkenine atar.
     * Eğer film bulunamazsa otomatik 404 döner.
     *
     * 📚 POLICY AUTHORIZATION:
     * $this->authorize('view', $movie) → MoviePolicy::view() metodunu çağırır.
     * Kullanıcı filmin sahibi değilse 403 Forbidden döner.
     */
    public function show(Movie $movie)
    {
        $this->authorize('view', $movie);

        // Bu filmin TMDB ID'si varsa benzer film önerilerini çek (24 saat cache)
        $similarMovies = [];
        if ($movie->tmdb_id) {
            $cacheKey = 'movie_similar_' . $movie->tmdb_id;
            $similarMovies = Cache::remember($cacheKey, now()->addHours(24), function () use ($movie) {
                $response = $this->tmdb->getSimilar($movie->tmdb_id);
                return $response?->successful() ? ($response->json()['results'] ?? []) : [];
            });
            $similarMovies = collect($similarMovies)->take(6);
        }

        return view('movies.show', compact('movie', 'similarMovies'));
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
                $res = $response?->successful() ? ($response->json()['results'] ?? []) : [];

                if (empty($res)) {
                    $fallbackResponse = $this->tmdb->getSimilar($lastMovie->tmdb_id);
                    $res = $fallbackResponse?->successful() ? ($fallbackResponse->json()['results'] ?? []) : [];
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
            return $response?->successful() ? ($response->json()['results'] ?? []) : [];
        });

        if (!empty($results)) {
            $nowPlaying = collect($results)
                ->whereNotIn('id', $myMovieIds)
                ->take(12);
        }

        return view('movies.now_playing', compact('nowPlaying'));
    }
}
