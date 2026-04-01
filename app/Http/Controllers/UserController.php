<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 📚 KULLANICI CONTROLLER
 *
 * Kullanıcı profili görüntüleme ve kullanıcı arama işlemleri.
 *
 * @KAVRAM: Public vs Private Profile
 * - is_public=true → Herkes görebilir
 * - is_public=false → Sadece takip edenler görebilir (veya sadece kendisi)
 */
class UserController extends Controller
{
    /**
     * Kullanıcı arama sayfası / keşfet
     *
     * GET /users
     */
    public function index(Request $request)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $search = $request->input('search');

        $query = User::query()
            ->where('id', '!=', $currentUser->id) // Kendini hariç tut
            ->where('is_public', true)            // Sadece public profiller
            ->withCount([
                'movies' => fn($q) => $q->where('is_watched', true),
                'followers',
                'following'
            ]);

        // İsim veya email'e göre arama
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Popülerliğe göre sırala (en çok takipçi)
        $users = $query->orderByDesc('followers_count')->paginate(20);

        // Mevcut kullanıcının kimleri takip ettiğini al (UI için)
        $followingIds = $currentUser->following()->pluck('following_id')->toArray();

        return view('users.index', compact('users', 'search', 'followingIds'));
    }

    /**
     * Kullanıcı profil sayfası
     *
     * GET /users/{user}
     */
    public function show(User $user)
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        // Profil public değilse ve kendisi değilse erişim engelle
        if (!$user->is_public && $currentUser?->id !== $user->id) {
            // Takip ediyorsa göster
            if (!$currentUser || !$currentUser->isFollowing($user)) {
                abort(403, 'Bu profil gizli.');
            }
        }

        /**
         * 📚 N+1 QUERY OPTİMİZASYONU
         *
         * ÖNCE (6 sorgu):
         * $stats['watched'] = $user->movies()->where('is_watched', true)->count();
         * $stats['watchlist'] = $user->movies()->where('is_watched', false)->count();
         * ... her biri ayrı veritabanı sorgusu
         *
         * SONRA (1 sorgu):
         * Tüm istatistikleri tek sorguda al, SQL'de CASE WHEN ile grupla
         */
        $movieStats = $user->movies()
            ->selectRaw("
                COUNT(CASE WHEN is_watched = 1 THEN 1 END) as watched_count,
                COUNT(CASE WHEN is_watched = 0 THEN 1 END) as watchlist_count,
                COALESCE(SUM(CASE WHEN is_watched = 1 THEN runtime ELSE 0 END), 0) as total_runtime
            ")
            ->first();

        $stats = [
            'watched_count' => $movieStats->watched_count ?? 0,
            'watchlist_count' => $movieStats->watchlist_count ?? 0,
            'total_runtime' => $movieStats->total_runtime ?? 0,
            'collections_count' => $user->collections()->count(),
            'followers_count' => $user->followersCount(),
            'following_count' => $user->followingCount(),
        ];

        // Son izlenen filmler (en son 12 tanesi)
        $recentMovies = $user->movies()
            ->where('is_watched', true)
            ->orderByDesc('watched_at')
            ->take(12)
            ->get();

        // Favori filmler (en yüksek kişisel puan)
        $favoriteMovies = $user->movies()
            ->where('is_watched', true)
            ->where('personal_rating', '>=', 4)
            ->orderByDesc('personal_rating')
            ->take(12)
            ->get();

        // İzlenecekler (Watchlist)
        $watchlistMovies = $user->movies()
            ->where('is_watched', false)
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        // Vitrin filmleri
        $showcaseMovies = $user->showcase_movies;

        // Takip ettiği kullanıcılar
        $followingUsers = $user->following()
            ->withCount(['movies' => fn($q) => $q->where('is_watched', true)])
            ->take(12)
            ->get();

        $recentMovieActivities = $user->movies()
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(function ($movie) {
                if (! empty($movie->watched_at)) {
                    return [
                        'icon' => 'fa-check-circle',
                        'icon_class' => 'text-emerald-400',
                        'title' => $movie->title,
                        'description' => 'İzlendi olarak işaretlendi',
                        'at' => $movie->watched_at,
                    ];
                }

                return [
                    'icon' => 'fa-film',
                    'icon_class' => 'text-indigo-400',
                    'title' => $movie->title,
                    'description' => 'Film kaydı güncellendi',
                    'at' => $movie->updated_at,
                ];
            });

        $recentCollectionActivities = $user->collections()
            ->latest('updated_at')
            ->take(4)
            ->get()
            ->map(fn ($collection) => [
                'icon' => 'fa-layer-group',
                'icon_class' => 'text-teal-400',
                'title' => $collection->name,
                'description' => 'Koleksiyon güncellendi',
                'at' => $collection->updated_at,
            ]);

        $recentActivities = $recentMovieActivities
            ->concat($recentCollectionActivities)
            ->sortByDesc('at')
            ->take(8)
            ->values();

        // Takip durumu
        $isFollowing = $currentUser ? $currentUser->isFollowing($user) : false;
        $isOwnProfile = $currentUser && $currentUser->id === $user->id;

        return view('users.show', compact(
            'user', 'stats', 'recentMovies', 'favoriteMovies', 'watchlistMovies',
            'showcaseMovies', 'followingUsers', 'recentActivities', 'isFollowing', 'isOwnProfile'
        ));
    }

    /**
     * Aktivite akışı (takip edilen kullanıcıların son aktiviteleri)
     *
     * GET /feed
     */
    public function feed()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $period = request()->input('period', 'all');

        if (! in_array($period, ['all', 'today', 'week'], true)) {
            $period = 'all';
        }

        // Takip edilen kullanıcıların ID'leri
        $followingIds = $currentUser->following()->pluck('following_id');

        // Son eklenen/izlenen filmler
        $activitiesQuery = \App\Models\Movie::query()
            ->whereIn('user_id', $followingIds)
            ->where('is_watched', true)
            ->with('user')
            ->orderByDesc('watched_at');

        if ($period === 'today') {
            $activitiesQuery->where('watched_at', '>=', now()->startOfDay());
        } elseif ($period === 'week') {
            $activitiesQuery->where('watched_at', '>=', now()->subDays(7));
        }

        $activities = $activitiesQuery->paginate(20)->withQueryString();

        return view('users.feed', compact('activities', 'period'));
    }
}
