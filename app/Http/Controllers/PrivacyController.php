<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🔒 PRIVACY CONTROLLER
 *
 * @KAVRAM: wantsJson() Pattern
 *
 * Aynı endpoint hem form submit hem AJAX destekler:
 *   if ($request->wantsJson()) {
 *       return response()->json([...]);  // AJAX için
 *   }
 *   return back()->with('success', ...); // Form için
 *
 * Frontend'de fetch() kullanırken:
 *   headers: { 'Accept': 'application/json' }
 *   → Bu header wantsJson()'ı true yapar!
 */
class PrivacyController extends Controller
{
    /**
     * Arşiv gizliliğini değiştir
     *
     * @KAVRAM: Toggle Pattern
     *
     * is_public = !is_public → True ise False, False ise True yapar
     * Tek buton ile açma/kapama işlemi
     */
    public function toggleArchive(Request $request)
    {
        $user = Auth::user();

        $updates = [
            'is_public' => ! $user->is_public,
        ];

        // İlk kez public yapılıyorsa share_token oluştur
        if (! $user->is_public && blank($user->share_token)) {
            $updates['share_token'] = (string) Str::uuid();
        }

        $user->update($updates);
        $user->refresh();

        Log::info('toggle_archive_privacy', [
            'user_id' => $user->id,
            'is_public' => $user->is_public,
            'share_token_present' => ! blank($user->share_token),
        ]);

        $status = $user->is_public ? 'Arşiviniz artık herkese açık!' : 'Arşiviniz artık gizli.';

        // AJAX request ise JSON dön
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $status,
                'is_public' => $user->is_public,
                'share_url' => $user->is_public && $user->share_token
                    ? route('public.archive', ['token' => $user->share_token])
                    : null,
            ]);
        }

        return back()->with('success', $status);
    }

    /**
     * Koleksiyon gizliliğini değiştir
     */
    public function toggleCollection(Request $request, Collection $collection)
    {
        // Yetki kontrolü
        if ($collection->user_id !== Auth::id()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Yetkisiz işlem'], 403);
            }
            abort(403);
        }

        $updates = [
            'is_public' => ! $collection->is_public,
        ];

        if (! $collection->is_public && blank($collection->share_token)) {
            $updates['share_token'] = (string) Str::uuid();
        }

        $collection->update($updates);
        $collection->refresh();

        Log::info('toggle_collection_privacy', [
            'user_id' => Auth::id(),
            'collection_id' => $collection->id,
            'is_public' => $collection->is_public,
            'share_token_present' => ! blank($collection->share_token),
        ]);

        $status = $collection->is_public ? 'Koleksiyon artık herkese açık!' : 'Koleksiyon artık gizli.';

        // AJAX request ise JSON dön
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $status,
                'is_public' => $collection->is_public,
                'share_url' => $collection->is_public && $collection->share_token
                    ? route('public.collection', ['token' => $collection->share_token])
                    : null,
            ]);
        }

        return back()->with('success', $status);
    }

    /**
     * Yeni paylaşım linki oluştur (Token yenileme)
     */
    public function regenerateToken(Request $request)
    {
        $user = Auth::user();

        if ($request->has('collection_id')) {
            $collection = Collection::findOrFail($request->collection_id);
            if ($collection->user_id !== $user->id) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Yetkisiz işlem'], 403);
                }
                abort(403);
            }

            $collection->update(['share_token' => (string) Str::uuid()]);

            Log::info('regenerate_collection_share_token', [
                'user_id' => $user->id,
                'collection_id' => $collection->id,
            ]);

            $message = 'Koleksiyon paylaşım linki yenilendi.';

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'share_url' => route('public.collection', ['token' => $collection->share_token]),
                ]);
            }

            return back()->with('success', $message);
        }

        $user->update(['share_token' => (string) Str::uuid()]);

        Log::info('regenerate_archive_share_token', [
            'user_id' => $user->id,
        ]);

        $message = 'Arşiv paylaşım linki yenilendi.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'share_url' => route('public.archive', ['token' => $user->share_token]),
            ]);
        }

        return back()->with('success', $message);
    }
}
