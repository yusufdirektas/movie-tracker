<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            return back()
                ->with('info', 'Bu kullanıcıyı zaten takip ediyorsunuz.')
                ->with('info_action', 'Profildeki "Takibi Bırak" butonunu kullanabilirsin.');
        }

        $currentUser->follow($user);

        // 📚 AKTİVİTE KAYDET
        // Takip etme aksiyonunu feed'de göster
        Activity::logFollowed($currentUser, $user);

        Log::info('follow_user', [
            'follower_id' => $currentUser->id,
            'following_id' => $user->id,
        ]);

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
        if (! $currentUser->isFollowing($user)) {
            return back()
                ->with('info', 'Bu kullanıcıyı zaten takip etmiyorsunuz.')
                ->with('info_action', 'Takip etmek için profildeki "Takip Et" butonunu kullanabilirsin.');
        }

        $currentUser->unfollow($user);

        Log::info('unfollow_user', [
            'follower_id' => $currentUser->id,
            'following_id' => $user->id,
        ]);

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
            ->withCount(['movies' => fn ($q) => $q->where('is_watched', true)])
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
            ->withCount(['movies' => fn ($q) => $q->where('is_watched', true)])
            ->paginate(20);

        return view('users.following', compact('user', 'following'));
    }
}
