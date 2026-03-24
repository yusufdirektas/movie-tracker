<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 📚 TAKİP SİSTEMİ CONTROLLER
 *
 * Bu controller kullanıcıların birbirini takip etmesini yönetir.
 *
 * @KAVRAM: RESTful Resource Naming
 * - POST /users/{user}/follow   → Takip et (store)
 * - DELETE /users/{user}/follow → Takipten çık (destroy)
 *
 * @KAVRAM: Route Model Binding
 * Laravel URL'deki {user} parametresini otomatik olarak User modeline çevirir.
 * Örn: /users/5/follow → User::findOrFail(5) çalıştırılır
 */
class FollowController extends Controller
{
    /**
     * Bir kullanıcıyı takip et
     *
     * POST /users/{user}/follow
     */
    public function store(User $user)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Kendini takip edemezsin
        if ($currentUser->id === $user->id) {
            return back()->with('error', 'Kendinizi takip edemezsiniz.');
        }

        // Zaten takip ediyorsan
        if ($currentUser->isFollowing($user)) {
            return back()->with('info', 'Bu kullanıcıyı zaten takip ediyorsunuz.');
        }

        $currentUser->follow($user);

        // AJAX isteği ise JSON dön
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$user->name} takip edildi!",
                'followers_count' => $user->followersCount(),
            ]);
        }

        return back()->with('success', "{$user->name} takip edildi!");
    }

    /**
     * Bir kullanıcıyı takipten çık
     *
     * DELETE /users/{user}/follow
     */
    public function destroy(User $user)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Takip etmiyorsan
        if (!$currentUser->isFollowing($user)) {
            return back()->with('info', 'Bu kullanıcıyı zaten takip etmiyorsunuz.');
        }

        $currentUser->unfollow($user);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$user->name} takipten çıkıldı.",
                'followers_count' => $user->followersCount(),
            ]);
        }

        return back()->with('success', "{$user->name} takipten çıkıldı.");
    }

    /**
     * Bir kullanıcının takipçilerini listele
     *
     * GET /users/{user}/followers
     */
    public function followers(User $user)
    {
        $followers = $user->followers()
            ->withCount(['movies' => fn($q) => $q->where('is_watched', true)])
            ->paginate(20);

        return view('users.followers', compact('user', 'followers'));
    }

    /**
     * Bir kullanıcının takip ettiklerini listele
     *
     * GET /users/{user}/following
     */
    public function following(User $user)
    {
        $following = $user->following()
            ->withCount(['movies' => fn($q) => $q->where('is_watched', true)])
            ->paginate(20);

        return view('users.following', compact('user', 'following'));
    }
}
