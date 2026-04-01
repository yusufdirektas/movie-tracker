<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Movie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 📚 YORUM CONTROLLER
 *
 * @KAVRAM: Nested Resource Route
 *
 * URL: /movies/{movie}/comments
 * Bu yapı, yorumun hangi filme ait olduğunu URL'den alır.
 *
 * @KAVRAM: Form Request vs Inline Validation
 *
 * Basit validasyonlar için inline (bu dosyada) yeterli.
 * Karmaşık validasyonlar için Form Request sınıfı kullan.
 */
class CommentController extends Controller
{
    /**
     * Yeni yorum ekle
     *
     * @KAVRAM: Route Model Binding
     *
     * store(Movie $movie) → Laravel URL'deki {movie} ID'yi
     * otomatik olarak Movie modeline dönüştürür.
     */
    public function store(Request $request, Movie $movie): RedirectResponse
    {
        // Film sahibi mi kontrol et (sadece kendi filmlerine yorum yapabilir)
        if ($movie->user_id !== $request->user()->id) {
            abort(403, 'Bu filme yorum yapamazsınız.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'has_spoiler' => ['boolean'],
        ], [
            'body.required' => 'Yorum alanı boş bırakılamaz.',
            'body.max' => 'Yorum en fazla 500 karakter olabilir.',
        ]);

        // Yorum oluştur
        $movie->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'has_spoiler' => $validated['has_spoiler'] ?? false,
        ]);

        return redirect()
            ->route('movies.show', $movie)
            ->with('success', 'Yorumunuz eklendi!');
    }

    /**
     * Yorum güncelle
     */
    public function update(Request $request, Movie $movie, Comment $comment): RedirectResponse
    {
        // Sadece kendi yorumunu düzenleyebilir
        if ($comment->user_id !== $request->user()->id) {
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

        return redirect()
            ->route('movies.show', $movie)
            ->with('success', 'Yorumunuz güncellendi!');
    }

    /**
     * Yorum sil
     */
    public function destroy(Request $request, Movie $movie, Comment $comment): RedirectResponse
    {
        // Sadece kendi yorumunu silebilir
        if ($comment->user_id !== $request->user()->id) {
            abort(403);
        }

        $comment->delete();

        return redirect()
            ->route('movies.show', $movie)
            ->with('success', 'Yorumunuz silindi.');
    }
}
