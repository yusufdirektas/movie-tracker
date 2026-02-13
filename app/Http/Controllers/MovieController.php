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
        $totalMinutes = $movies->where('is_watched', true)->sum('runtime');
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

        $token = config('services.tmdb.token');

        $response = Http::withToken($token)
            ->get("https://api.themoviedb.org/3/movie/{$request->tmdb_id}", [
                'language' => 'tr-TR'
            ]);

        if ($response->successful()) {
            $movieData = $response->json();
            $user = Auth::user();

            // DEĞİŞİKLİK BURADA:
            // Formdan gelen 'is_watched' değerini boolean (true/false) olarak alıyoruz.
            // Eğer buton 1 gönderirse true, 0 gönderirse false olur.
            $isWatched = $request->boolean('is_watched');

            $user->movies()->create([
                'title'        => $movieData['title'],
                'poster_path'  => $movieData['poster_path'],
                'rating'       => $movieData['vote_average'],
                'runtime'      => $movieData['runtime'],
                'overview'     => $movieData['overview'],
                'release_date' => $movieData['release_date'],
                'is_watched'   => $isWatched, // Artık dinamik!
            ]);

            // Mesajı da duruma göre özelleştirelim
            $message = $isWatched
                ? 'Film "İzlediklerim" listesine eklendi!'
                : 'Film "İzlenecekler" listesine eklendi!';

            if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Film kaydedildi.']);
        }

        $message = $isWatched
            ? 'Film "İzlediklerim" listesine eklendi!'
            : 'Film "İzlenecekler" listesine eklendi!';

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

        return redirect()->route('movies.index')->with('success', 'Film silindi.');
    }
    public function import()
    {
        return view('movies.import');
    }
}
