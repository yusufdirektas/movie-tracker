<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentReaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 👍👎 REACTION CONTROLLER
 *
 * @KAVRAM: Toggle Pattern (Like/Unlike Mantığı)
 *
 * Kullanıcı like butonuna tıklarsa:
 * 1. Reaction YOK → Oluştur (is_like = true)
 * 2. Reaction VAR ve LIKE → SİL (unlike)
 * 3. Reaction VAR ve DISLIKE → GÜNCELLE (is_like = true)
 *
 * Aynı mantık dislike için de geçerli.
 *
 * @KAVRAM: JSON Response (AJAX)
 *
 * Frontend Alpine.js ile AJAX gönderir.
 * Backend JSON döner: { liked: true, likeCount: 5, dislikeCount: 2 }
 * Frontend sayaçları günceller (page reload YOK!)
 */
class ReactionController extends Controller
{
    /**
     * Like toggle (Beğen/Beğenmekten vazgeç)
     *
     * @KAVRAM: updateOrCreate() Methodu
     *
     * Laravel'in akıllı yöntemi:
     * - Kayıt VARSA → GÜNCELLE
     * - Kayıt YOKSA → OLUŞTUR
     *
     * Parametreler:
     * 1. WHERE koşulu: ['comment_id' => X, 'user_id' => Y]
     * 2. SET değerleri: ['is_like' => true]
     *
     * Örnek:
     *   updateOrCreate(
     *     ['user_id' => 1, 'comment_id' => 5], // Bu kayıt var mı?
     *     ['is_like' => true]                   // Varsa güncelle, yoksa oluştur
     *   );
     */
    public function like(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        // Mevcut reaction'ı bul
        $reaction = CommentReaction::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            // Zaten like yapmışsa → SİL (unlike)
            if ($reaction->is_like) {
                $reaction->delete();
                $liked = false;
            } else {
                // Dislike yapmışsa → Like'a çevir
                $reaction->update(['is_like' => true]);
                $liked = true;
            }
        } else {
            // Hiç reaction yoksa → Yeni like oluştur
            CommentReaction::create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'is_like' => true,
            ]);
            $liked = true;
        }

        // Güncel sayıları hesapla
        return response()->json([
            'liked' => $liked,
            'disliked' => false,
            'likeCount' => $comment->reactions()->where('is_like', true)->count(),
            'dislikeCount' => $comment->reactions()->where('is_like', false)->count(),
        ]);
    }

    /**
     * Dislike toggle (Beğenme/Vazgeç)
     */
    public function dislike(Request $request, Comment $comment): JsonResponse
    {
        $user = $request->user();

        // Mevcut reaction'ı bul
        $reaction = CommentReaction::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($reaction) {
            // Zaten dislike yapmışsa → SİL (un-dislike)
            if (! $reaction->is_like) {
                $reaction->delete();
                $disliked = false;
            } else {
                // Like yapmışsa → Dislike'a çevir
                $reaction->update(['is_like' => false]);
                $disliked = true;
            }
        } else {
            // Hiç reaction yoksa → Yeni dislike oluştur
            CommentReaction::create([
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'is_like' => false,
            ]);
            $disliked = true;
        }

        // Güncel sayıları hesapla
        return response()->json([
            'liked' => false,
            'disliked' => $disliked,
            'likeCount' => $comment->reactions()->where('is_like', true)->count(),
            'dislikeCount' => $comment->reactions()->where('is_like', false)->count(),
        ]);
    }
}
