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

        // 1. Arama Filtresi (Senin tercih ettiğin yöntem)
        $search = mb_strtolower($request->input('search'), 'UTF-8');

        // 2. Kullanıcının Tüm Filmlerini Çek
        $movies = $user->movies()->latest()->get();

        // --- İSTATİSTİK HESAPLAMALARI (Backend Logic) ---

        // Toplam Film Sayısı
        $totalMovies = $movies->count();

        // İzlenen Film Sayısı
        $watchedCount = $movies->where('is_watched', true)->count();

        // Toplam İzlenen Süre (Dakika -> Saate Çevirme)
        // Sadece 'is_watched' olanların 'runtime' değerlerini topluyoruz
        // Daha güvenli: null değerleri 0 kabul eder ve sayısal (int) toplama yapar
        $totalMinutes = $movies->where('is_watched', true)->sum(function ($movie) {
            return (int) ($movie->runtime ?? 0);
        });
        $totalHours = floor($totalMinutes / 60); // Tam saat
        $remainingMinutes = $totalMinutes % 60;  // Kalan dakika

        // En Yüksek Puanlı Film (Dashboard'daki Highlight Kartı İçin)
        $highestRated = $movies->sortByDesc('rating')->first();

        // Verileri view'a paketleyip gönderiyoruz (compact fonksiyonu bu işi yapar)
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
}
