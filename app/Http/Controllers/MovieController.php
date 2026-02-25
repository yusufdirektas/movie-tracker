<?php

namespace App\Http\Controllers;


use App\Models\Movie;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MovieController extends Controller
{
    /**
     * Kullanıcının film arşivini ve istatistiklerini listeler.
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // 1. Arama Filtresi
        $search = mb_strtolower($request->input('search'), 'UTF-8');

        // 2. Kullanıcının Tüm Filmlerini Çek
        $movies = $user->movies()->latest()->get();

        // --- İSTATİSTİK HESAPLAMALARI ---
        $totalMovies = $movies->count();
        $watchedCount = $movies->where('is_watched', true)->count();

        $totalMinutes = $movies->where('is_watched', true)->sum(function ($movie) {
            return (int) ($movie->runtime ?? 0);
        });

        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        $highestRated = $movies->sortByDesc('rating')->first();

        // SADECE movies.index'e yönlendiriyoruz
        return view('movies.index', compact(
            'movies',
            'search',
            'totalMovies',
            'watchedCount',
            'totalHours',
            'remainingMinutes',
            'highestRated'
        ));
    }

    /**
     * Yeni film keşfetme sayfasını gösterir.
     */
    public function create()
    {

        return view('movies.create');
    }

    /**
     * TMDB'den anlık arama yapan API fonksiyonu.
     */
    public function apiSearch(Request $request)
    {
        $query = mb_strtolower($request->input('query'), 'UTF-8');

        if (!$query) return response()->json([]);

        $token = config('services.tmdb.token');

        $response = Http::withToken($token)
            ->get('https://api.themoviedb.org/3/search/movie', [
                'query' => $query,
                'language' => 'tr-TR',
                'include_adult' => false,
            ]);

        if ($response->successful()) {
            return response()->json(collect($response->json()['results'])->take(6));
        }

        return response()->json([]);
    }

    /**
     * Seçilen filmi ve süresini kaydeder.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tmdb_id' => 'required',
        ]);

        $user = Auth::user();

        // FİLM ZATEN EKLİ Mİ KONTROLÜ
        $alreadyExists = $user->movies()->where('tmdb_id', $request->tmdb_id)->exists();

        if ($alreadyExists) {
            // Eğer istek arkaplandan (AJAX/JSON) geliyorsa:
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu film zaten arşivinde mevcut!'
                ]);
            }
            // Normal form ile geliyorsa:
            return back()->with('error', 'Bu film zaten arşivinde mevcut!');
        }

        $token = config('services.tmdb.token');
        $request->validate([
            'tmdb_id' => 'required',
        ]);

        $token = config('services.tmdb.token');

        // 'append_to_response=credits' ekleyerek yönetmen bilgisini de istiyoruz
        $response = Http::withToken($token)
            ->get("https://api.themoviedb.org/3/movie/{$request->tmdb_id}", [
                'language' => 'tr-TR',
                'append_to_response' => 'credits'
            ]);

        if ($response->successful()) {
            $movieData = $response->json();

            // Yönetmeni bulalım (Crew dizisi içinde job'ı 'Director' olan kişi)
            $director = collect($movieData['credits']['crew'] ?? [])
                ->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';

            $user = Auth::user();
            $isWatched = $request->boolean('is_watched');

            $user->movies()->create([
                'tmdb_id'      => $movieData['id'],
                'title'        => $movieData['title'],
                'director'     => $director, // Artık veritabanına kaydolacak
                'poster_path'  => $movieData['poster_path'],
                'rating'       => $movieData['vote_average'],
                'runtime'      => $movieData['runtime'],
                // Eğer özet boş gelirse veritabanına null olarak kaydet, böylece arayüzdeki "Özet yok" yazısı çalışsın
                'overview'     => empty($movieData['overview']) ? null : $movieData['overview'],
                'release_date' => $movieData['release_date'],
                'is_watched'   => $isWatched,
            ]);

            $message = $isWatched ? 'Film listeye eklendi!' : 'Film izleneceklere eklendi!';
            return redirect()->route('movies.index')->with('success', $message);
        }

        return back()->with('error', 'Film bilgileri alınamadı.');
    }

    public function show(Movie $movie)
    {
        return redirect()->route('movies.index');
    }

    public function update(Request $request, Movie $movie)
    {
        if ($movie->user_id !== Auth::id()) {
            abort(403);
        }

        $movie->update([
            'is_watched' => !$movie->is_watched
        ]);

        return back()->with('success', 'Durum güncellendi.');
    }

    public function destroy(Movie $movie)
    {
        if ($movie->user_id !== Auth::id()) {
            abort(403);
        }

        $movie->delete();

        return back()->with('success', 'Film silindi.');
    }
    public function import()
    {
        return view('movies.import');
    }
    /**
     * Sana Özel Öneriler Sayfasını Gösterir.
     */
    public function recommendations()
    {
        /** @var User $user */
        $user = Auth::user();

        $movies = $user->movies()->latest()->get();
        $lastMovie = $movies->first(); // En son eklenen film
        $recommendations = [];

        if ($lastMovie && $lastMovie->tmdb_id) {
            $response = Http::withToken(config('services.tmdb.token'))
                ->get("https://api.themoviedb.org/3/movie/{$lastMovie->tmdb_id}/recommendations", [
                    'language' => 'tr-TR',
                ]);

            if ($response->successful()) {
                $allRecs = collect($response->json()['results']);
                $myMovieIds = $movies->pluck('tmdb_id')->toArray();

                // Tam sayfa olduğu için artık 6 değil, 12 film çekiyoruz!
                $recommendations = $allRecs->whereNotIn('id', $myMovieIds)
                    ->shuffle()
                    ->take(12);
            }
        }

        return view('movies.recommendations', compact('recommendations', 'lastMovie'));
    }
    /**
     * Vizyondaki Filmler Sayfasını Gösterir.
     */
    public function nowPlaying()
    {
        /** @var User $user */
        $user = Auth::user();

        // Kullanıcının mevcut filmlerini alalım ki vizyondakilerden eleyelim
        $myMovieIds = $user->movies()->pluck('tmdb_id')->toArray();
        $nowPlaying = [];

        // TMDB 'now_playing' uç noktasına istek atıyoruz (region=TR ile Türkiye vizyonu)
        $response = \Illuminate\Support\Facades\Http::withToken(config('services.tmdb.token'))
            ->get("https://api.themoviedb.org/3/movie/now_playing", [
                'language' => 'tr-TR',
                'region' => 'TR',
            ]);

        if ($response->successful()) {
            $allResults = collect($response->json()['results']);

            // Arşivinde olmayanları süz ve 12 tane al
            $nowPlaying = $allResults->whereNotIn('id', $myMovieIds)->take(12);
        }

        return view('movies.now_playing', compact('nowPlaying'));
    }
}
