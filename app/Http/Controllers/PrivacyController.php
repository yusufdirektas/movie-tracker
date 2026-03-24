<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PrivacyController extends Controller
{
    /**
     * Arşiv gizliliğini değiştir
     */
    public function toggleArchive()
    {
        $user = Auth::user();

        $updates = [
            'is_public' => ! $user->is_public,
        ];

        if (! $user->is_public && blank($user->share_token)) {
            $updates['share_token'] = (string) Str::uuid();
        }

        $user->update($updates);
        $user->refresh();

        $status = $user->is_public ? 'Arşiviniz artık herkese açık!' : 'Arşiviniz artık gizli.';

        return back()->with('success', $status);
    }

    /**
     * Koleksiyon gizliliğini değiştir
     */
    public function toggleCollection(Collection $collection)
    {
        // Yetki kontrolü (Policy veya manual)
        if ($collection->user_id !== Auth::id()) {
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

        $status = $collection->is_public ? 'Koleksiyon artık herkese açık!' : 'Koleksiyon artık gizli.';

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
                abort(403);
            }

            $collection->update(['share_token' => (string) Str::uuid()]);

            return back()->with('success', 'Koleksiyon paylaşım linki yenilendi.');
        }

        $user->update(['share_token' => (string) Str::uuid()]);

        return back()->with('success', 'Arşiv paylaşım linki yenilendi.');
    }
}
