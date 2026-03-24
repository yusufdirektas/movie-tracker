<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\Contracts\MovieRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 📚 REPOSITORY PATTERN KULLANIMI
 * 
 * Eski hali:
 *   $user->movies()->unwatched()->searchByTitle(...)->paginate(20);
 * 
 * Yeni hali:
 *   $this->movieRepository->getUnwatchedMovies($userId, $filters);
 * 
 * Değişen ne?
 * - Controller artık veritabanı detaylarını bilmiyor
 * - Tüm sorgu mantığı Repository'de
 * - Test yazmak çok daha kolay
 * - Kod daha okunabilir
 */
class WatchlistController extends Controller
{
    /**
     * 📚 CONSTRUCTOR INJECTION
     * 
     * Repository'yi constructor'da alıyoruz (Dependency Injection).
     * Laravel, AppServiceProvider'daki binding sayesinde
     * otomatik olarak MovieRepository instance'ı verir.
     * 
     * Type-hint olarak INTERFACE kullanıyoruz, concrete class değil.
     * Bu sayede yarın farklı bir repository kullanmak istersek
     * sadece ServiceProvider'ı değiştiririz.
     */
    public function __construct(
        protected MovieRepositoryInterface $movieRepository
    ) {}

    /**
     * SADECE İZLENMEYECEKLER (İzleme Listem)
     */
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Filtreleri hazırla
        $filters = [
            'search' => mb_strtolower((string) $request->input('search', ''), 'UTF-8'),
            'genre'  => $request->input('genre'),
            'sort'   => $request->input('sort', 'updated_at'),
        ];

        // Repository'den veri al - Tek satırda!
        $movies = $this->movieRepository->getUnwatchedMovies($user->id, $filters);
        
        // İstatistikler ve türler
        $totalMovies = $user->movies()->unwatched()->count();
        $availableGenres = $this->movieRepository->getAvailableGenres($user->id, false);
        
        // Koleksiyonlar (Toplu işlem dropdown'ı için)
        $collections = $user->collections()->orderBy('name')->get();

        if ($request->ajax()) {
            return view('movies.partials._watchlist_grid', compact('movies'));
        }

        return view('movies.watchlist', [
            'movies' => $movies,
            'search' => $filters['search'],
            'genre' => $filters['genre'],
            'sort' => $filters['sort'],
            'availableGenres' => $availableGenres,
            'totalMovies' => $totalMovies,
            'collections' => $collections,
        ]);
    }
}
