<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BulkActionController extends Controller
{
    /**
     * Seçili filmleri siler
     */
    public function delete(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id',
        ]);

        // Yalnızca kullanıcının kendi filmlerini silebildiğinden emin olalım
        $deletedCount = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->delete();

        Log::info('bulk_delete_movies', [
            'user_id' => Auth::id(),
            'requested_movie_count' => count($request->movie_ids),
            'deleted_movie_count' => $deletedCount,
        ]);

        return back()->with('success', $deletedCount.' film başarıyla silindi.');
    }

    /**
     * Seçili filmleri "İzlendi" olarak işaretler
     */
    public function markAsWatched(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id',
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

        Log::info('bulk_mark_movies_watched', [
            'user_id' => Auth::id(),
            'requested_movie_count' => count($request->movie_ids),
            'updated_movie_count' => $movies->count(),
        ]);

        return back()->with('success', $movies->count().' film izlendi olarak işaretlendi.');
    }

    /**
     * Seçili filmleri "İzlenmedi" olarak işaretler
     */
    public function markAsUnwatched(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id',
        ]);

        $updatedCount = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->update([
                'is_watched' => false,
                'watched_at' => null,
            ]);

        Log::info('bulk_mark_movies_unwatched', [
            'user_id' => Auth::id(),
            'requested_movie_count' => count($request->movie_ids),
            'updated_movie_count' => $updatedCount,
        ]);

        return back()->with('success', $updatedCount.' film izlenmedi olarak işaretlendi.');
    }

    /**
     * Seçili filmleri belirtilen koleksiyona ekler
     */
    public function addToCollection(Request $request)
    {
        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'exists:movies,id',
            'collection_id' => 'required|exists:collections,id',
        ]);

        // Koleksiyonun kullanıcıya ait olduğunu doğrula
        $collection = Collection::where('user_id', Auth::id())->findOrFail($request->collection_id);

        // Kullanıcının kendi filmlerini seçtiğini doğrula
        $validMovieIds = Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();

        // syncWithoutDetaching ile mevcutları koruyarak yenilerini ekleriz
        $collection->movies()->syncWithoutDetaching($validMovieIds);

        Log::info('bulk_add_movies_to_collection', [
            'user_id' => Auth::id(),
            'collection_id' => $collection->id,
            'requested_movie_count' => count($request->movie_ids),
            'attached_movie_count' => count($validMovieIds),
        ]);

        return back()->with('success', count($validMovieIds).' film koleksiyona başarıyla eklendi.');
    }
}
