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
use Illuminate\Support\Str;

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

        if (blank($user->share_token)) {
            $user->forceFill(['share_token' => (string) Str::uuid()])->save();
            $user->refresh();
        }

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
        $availableGenres = Movie::getAvailableGenres($user->id, true);

        // Koleksiyonlar (Toplu işlem toolbar'ı için)
        $collections = $user->collections()->orderBy('name')->get();

        if ($request->ajax()) {
            return view('movies.partials._grid', compact('movies'));
        }

        return view('movies.index', compact(
            'movies', 'search', 'filter', 'genre', 'availableGenres', 'sort',
            'totalMovies', 'watchedCount', 'totalHours', 'remainingMinutes', 'highestRated',
            'collections'
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

        // Import sayfası smart=1 parametresi gönderir → yazım hatası toleranslı arama
        if ($request->boolean('smart')) {
            $result = $this->tmdb->smartSearch($query);
            return response()->json([
                'results'         => collect($result['results'])->take(6),
                'corrected'       => $result['corrected'],
                'corrected_query' => $result['corrected_query'],
                'suggestions'     => collect($result['suggestions'])->take(5),
            ]);
        }

        // Normal arama (create sayfasındaki canlı arama için)
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
        $mediaType = $request->input('media_type', 'movie');
        $alreadyExists = $user->movies()
            ->where('tmdb_id', $request->tmdb_id)
            ->where('media_type', $mediaType)
            ->exists();

        if ($alreadyExists) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Bu içerik zaten arşivinde mevcut!'], 400);
            }
            return back()->with('error', 'Bu içerik zaten arşivinde mevcut!');
        }

        // media_type'a göre film veya dizi detayını çek
        if ($mediaType === 'tv') {
            $response = $this->tmdb->getTvDetails($request->tmdb_id);
        } else {
            $response = $this->tmdb->getMovieDetails($request->tmdb_id);
        }

        if ($response?->successful()) {
            $data = $response->json();
            $isWatched = $request->boolean('is_watched');
            $genres = collect($data['genres'] ?? [])->pluck('name')->toArray();

            if ($mediaType === 'tv') {
                // Dizi: created_by → yönetmen, name → başlık, episode_run_time → süre
                $creator = collect($data['created_by'] ?? [])->first()['name']
                    ?? collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name']
                    ?? 'Bilinmiyor';
                $runtime = $data['episode_run_time'][0] ?? $data['last_episode_to_air']['runtime'] ?? null;

                $user->movies()->create([
                    'tmdb_id'      => $data['id'],
                    'media_type'   => 'tv',
                    'title'        => $data['name'] ?? $data['original_name'],
                    'director'     => $creator,
                    'genres'       => $genres,
                    'poster_path'  => $data['poster_path'],
                    'rating'       => $data['vote_average'],
                    'runtime'      => $runtime,
                    'overview'     => empty($data['overview']) ? null : $data['overview'],
                    'release_date' => $data['first_air_date'] ?? null,
                    'is_watched'   => $isWatched,
                    'watched_at'   => $isWatched ? now() : null,
                ]);
            } else {
                // Film: credits.crew → yönetmen, title → başlık
                $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';

                $user->movies()->create([
                    'tmdb_id'      => $data['id'],
                    'media_type'   => 'movie',
                    'title'        => $data['title'],
                    'director'     => $director,
                    'genres'       => $genres,
                    'poster_path'  => $data['poster_path'],
                    'rating'       => $data['vote_average'],
                    'runtime'      => $data['runtime'],
                    'overview'     => empty($data['overview']) ? null : $data['overview'],
                    'release_date' => $data['release_date'],
                    'is_watched'   => $isWatched,
                    'watched_at'   => $isWatched ? now() : null,
                ]);
            }

            $label = $mediaType === 'tv' ? 'Dizi' : 'Film';
            $message = $isWatched ? "{$label} listeye eklendi!" : "{$label} izleneceklere eklendi!";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return redirect()->route('movies.index')->with('success', $message);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Bilgiler alınamadı.'], 500);
        }
        return back()->with('error', 'Bilgiler alınamadı.');
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
        // Kullanıcının koleksiyonlarını al (Koleksiyona Ekle dropdown'u için)
        $collections = Auth::user()->collections()->orderBy('name')->get();
        $movieCollectionIds = $movie->collections()->pluck('collections.id')->toArray();

        return view('movies.show', compact('movie', 'similarMovies', 'collections', 'movieCollectionIds'));
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
