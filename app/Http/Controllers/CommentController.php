<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 📚 YORUM CONTROLLER
 *
 * @KAVRAM: Global Comments (Public/Shared Comments)
 *
 * Eski sistem:
 * - Kullanıcı kendi filmlerine yorum yapardı (private)
 * - commentable_id → user'ın movie record'ı
 *
 * Yeni sistem:
 * - Herkes aynı TMDB filmine yorum yapabilir (public)
 * - tmdb_id → TMDB'deki film ID (Fight Club = 550)
 * - Tüm kullanıcılar aynı yorumları görür
 *
 * Örnek:
 *   User A, Fight Club'a yorum yapar (tmdb_id: 550)
 *   User B, Fight Club sayfasında User A'nın yorumunu görür
 *
 * @KAVRAM: Route Model Binding
 *
 * {movie} parametresi → Kullanıcının kendi Movie kaydı
 * tmdb_id → Global film ID'si
 */
class CommentController extends Controller
{
    /**
     * Yeni yorum ekle (Global/Public)
     *
     * @KAVRAM: Global Comments Pattern
     *
     * Artık kullanıcı kendi filmlerine değil, TMDB filmine yorum yapar.
     * tmdb_id ile kaydedilir, böylece tüm kullanıcılar aynı yorumları görür.
     *
     * Akış:
     * 1. $movie → Kullanıcının kendi movie kaydı (sadece tmdb_id almak için)
     * 2. Comment::create() → tmdb_id ile kaydet (polymorphic ilişki YOK)
     * 3. Tüm kullanıcılar aynı tmdb_id'li yorumları görür
     */
    public function store(Request $request, Movie $movie)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'has_spoiler' => ['boolean'],
        ], [
            'body.required' => 'Yorum alanı boş bırakılamaz.',
            'body.max' => 'Yorum en fazla 500 karakter olabilir.',
        ]);

        // Global yorum oluştur (tmdb_id ile)
        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'tmdb_id' => $movie->tmdb_id,
            'body' => $validated['body'],
            'has_spoiler' => $validated['has_spoiler'] ?? false,
        ]);

        // User'ı yükle
        $comment->load('user');

        // AJAX request ise JSON dön
        if ($request->wantsJson()) {
            return response()->json([
                'comment' => [
                    'id' => $comment->id,
                    'user_id' => $comment->user_id,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->avatar,
                    ],
                    'body' => $comment->body,
                    'has_spoiler' => (bool)$comment->has_spoiler,
                    'created_at' => $comment->created_at->toISOString(),
                    'created_at_human' => $comment->created_at->diffForHumans(),
                    'updated_at' => $comment->updated_at->toISOString(),
                    'is_edited' => false,
                    'like_count' => 0,
                    'dislike_count' => 0,
                    'user_reaction' => null,
                ],
            ], 201);
        }

        // Normal request ise redirect
        return redirect()
            ->route('movies.show', $movie)
            ->with('success', 'Yorumunuz herkese açık olarak yayınlandı!');
    }

    /**
     * Yorum güncelle (AJAX destekli)
     */
    public function update(Request $request, Movie $movie, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Yetkisiz'], 403);
            }
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'has_spoiler' => ['boolean'],
        ]);

        $comment->update([
            'body' => $validated['body'],
            'has_spoiler' => $validated['has_spoiler'] ?? false,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Yorum güncellendi!',
                'comment' => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'has_spoiler' => (bool)$comment->has_spoiler,
                    'updated_at' => $comment->updated_at->toISOString(),
                    'is_edited' => true,
                ],
            ]);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Yorumunuz güncellendi!');
    }

    /**
     * Yorum sil (AJAX destekli)
     */
    public function destroy(Request $request, Movie $movie, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Yetkisiz'], 403);
            }
            abort(403);
        }

        $comment->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Yorum silindi!',
            ]);
        }

        return redirect()->route('movies.show', $movie)->with('success', 'Yorumunuz silindi.');
    }
}
