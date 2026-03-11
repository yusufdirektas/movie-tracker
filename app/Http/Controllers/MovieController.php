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

        // İSTATİSTİKLERİ SAYFALANDIRMADAN BAĞIMSIZ ÇEKİYORUZ
        $totalMovies = $user->movies()->count();
        $baseWatchedQuery = clone $user->movies()->watched();

        $watchedCount = $baseWatchedQuery->count();
        $totalMinutes = $baseWatchedQuery->sum('runtime');
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        // `clone` etmezsek yukarıdaki sum() ve count() sonrası query bozulabilir. Zaten modelin yeni bir query nesnesi oluşturduğundan yukarıda sıkıntı yok ama en doğrusu ayrı çalışmak.
        $highestRated = $user->movies()->watched()->orderByDesc('rating')->first();

        // ---------------------------------------------------------------------
        // 📚 REFACTORING (KOD İYİLEŞTİRME) SONRASI:
        // Eskiden burada iç içe geçmiş bir sürü if bloğu ve sorgu vardı.
        // Şimdi tüm karmaşıklık Model (Movie.php) içindeki Scope'lara taşındı.
        // Kod bir hikaye okur gibi yukarıdan aşağıya akıyor.
        // ---------------------------------------------------------------------

        $filter = $request->input('filter', 'all');
        $search = mb_strtolower($request->input('search'), 'UTF-8');
        $genre = $request->input('genre');
        $sort = $request->input('sort', 'updated_at');

        $allowedSorts = [
            'updated_at'      => 'desc',
            'title'           => 'asc',
            'rating'          => 'desc',
            'personal_rating' => 'desc',
            'release_date'    => 'desc',
            'runtime'         => 'desc',
        ];

        $query = $user->movies()
            ->watched()
            ->searchByTitle($search)
            ->filterByGenre($genre)
            ->applySort($sort, $allowedSorts);

        if ($filter === 'favorites') {
            $query->where('personal_rating', '>=', 4);
        }

        $movies = $query->paginate(20)->withQueryString();

        // Türleri yine Model üzerinden alıyoruz
        $availableGenres = App\Models\Movie::getAvailableGenres($user->id, true);

        return view('movies.index', compact(
            'movies', 'search', 'filter', 'genre', 'availableGenres', 'sort',
            'totalMovies', 'watchedCount', 'totalHours', 'remainingMinutes', 'highestRated'
        ));
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


}
