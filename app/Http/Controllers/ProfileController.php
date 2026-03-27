<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * 📚 PROFİL CONTROLLER
 *
 * @KAVRAM: File Upload
 * - store() metodu dosyayı storage'a kaydeder
 * - Storage::disk('public') → storage/app/public klasörü
 * - php artisan storage:link → public/storage symlink oluşturur
 */
class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Vitrin için seçilebilecek filmler (izlenmiş filmler)
        $availableMovies = $user->movies()
            ->where('is_watched', true)
            ->orderBy('title')
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

        return view('profile.edit', [
            'user' => $user,
            'availableMovies' => $availableMovies,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * 📚 Avatar yükleme
     *
     * @KAVRAM: $request->file()
     * - Yüklenen dosyayı UploadedFile objesi olarak alır
     * - store() metodu unique dosya adı oluşturur
     * - 'avatars' → storage/app/public/avatars klasörüne kaydeder
     *
     * @KAVRAM: Storage::delete()
     * - Eski avatarı siler (disk alanı tasarrufu)
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // Max 2MB
        ]);

        $user = $request->user();

        // Eski avatarı sil
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Yeni avatarı kaydet
        // store() otomatik unique dosya adı oluşturur: avatars/abc123xyz.jpg
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * 📚 Avatar silme
     */
    public function deleteAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-deleted');
    }

    /**
     * 📚 Bio güncelleme
     */
    public function updateBio(Request $request): RedirectResponse
    {
        $request->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'is_public' => ['boolean'],
            'show_recent_activities' => ['boolean'],
        ]);

        $request->user()->update([
            'bio' => $request->bio,
            'is_public' => $request->boolean('is_public'),
            'show_recent_activities' => $request->boolean('show_recent_activities'),
        ]);

        return Redirect::route('profile.edit')->with('status', 'bio-updated');
    }

    /**
     * 📚 Vitrin filmlerini güncelle
     *
     * @KAVRAM: array validation
     * - 'showcase_movies.*' → array içindeki her eleman için kural
     * - exists:movies,id → movies tablosunda var mı kontrol
     */
    public function updateShowcase(Request $request): RedirectResponse
    {
        $request->validate([
            'showcase_movies' => ['nullable', 'array', 'max:5'], // Max 5 film
            'showcase_movies.*' => ['integer', 'exists:movies,id'],
        ]);

        // Sadece kullanıcının kendi filmlerini kabul et
        $validMovieIds = $request->user()
            ->movies()
            ->whereIn('id', $request->showcase_movies ?? [])
            ->pluck('id')
            ->toArray();

        $request->user()->update([
            'showcase_movies' => $validMovieIds,
        ]);

        return Redirect::route('profile.edit')->with('status', 'showcase-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Avatarı da sil
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
