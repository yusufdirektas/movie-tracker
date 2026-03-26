<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BulkActionController extends Controller
{
    /**
     * Seçili filmleri siler
     */
    public function delete(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id'
        ]);

        // Yalnızca kullanıcının kendi filmlerini silebildiğinden emin olalım
        $deletedCount = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->delete();

        if ($deletedCount === 0) {
            return back()
                ->with('info', 'Silinecek uygun film bulunamadı.')
                ->with('info_action', 'Lütfen yalnızca kendi arşivindeki filmleri seçtiğinden emin ol.');
        }

        return back()->with('success', $deletedCount . ' film başarıyla silindi.');
    }

    /**
     * Seçili filmleri "İzlendi" olarak işaretler
     */
    public function markAsWatched(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id'
        ]);

        // Her filmi tek tek güncelliyoruz: zaten watched_at varsa dokunma, yoksa şimdiyi yaz.
        // NOT: DB::raw('COALESCE(watched_at, NOW())') SQLite'ta çalışmaz çünkü NOW() MySQL fonksiyonudur.
        // Bu yüzden PHP tarafında Carbon ile çözüyoruz.
        $movies = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->get();

        foreach ($movies as $movie) {
            $movie->update([
                'is_watched' => true,
                'watched_at' => $movie->watched_at ?? now(),
            ]);
        }

        if ($movies->isEmpty()) {
            return back()
                ->with('info', 'İşaretlenecek uygun film bulunamadı.')
                ->with('info_action', 'Lütfen yalnızca kendi arşivindeki filmleri seçtiğinden emin ol.');
        }

        return back()->with('success', $movies->count() . ' film izlendi olarak işaretlendi.');
    }

    /**
     * Seçili filmleri "İzlenmedi" olarak işaretler
     */
    public function markAsUnwatched(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id'
        ]);

        $updatedCount = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->update([
                'is_watched' => false,
                'watched_at' => null
            ]);

        if ($updatedCount === 0) {
            return back()
                ->with('info', 'Güncellenecek uygun film bulunamadı.')
                ->with('info_action', 'Lütfen yalnızca kendi arşivindeki filmleri seçtiğinden emin ol.');
        }

        return back()->with('success', $updatedCount . ' film izlenmedi olarak işaretlendi.');
    }

    /**
     * Seçili filmleri belirtilen koleksiyona ekler
     */
    public function addToCollection(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id',
            'collection_id' => 'required|exists:collections,id'
        ]);

        // Koleksiyonun kullanıcıya ait olduğunu doğrula
        $collection = Collection::where('user_id', Auth::id())->findOrFail($request->collection_id);

        // Kullanıcının kendi filmlerini seçtiğini doğrula
        $validMovieIds = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();

        if (empty($validMovieIds)) {
            return back()
                ->with('info', 'Koleksiyona eklenecek uygun film bulunamadı.')
                ->with('info_action', 'Seçim yapıp tekrar deneyebilirsin.');
        }

        // syncWithoutDetaching ile mevcutları koruyarak yenilerini ekleriz
        $collection->movies()->syncWithoutDetaching($validMovieIds);

        return back()->with('success', count($validMovieIds) . ' film koleksiyona başarıyla eklendi.');
    }
}
