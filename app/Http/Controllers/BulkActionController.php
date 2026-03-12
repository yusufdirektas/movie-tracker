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
        Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->delete();

        return back()->with('success', count($request->movie_ids) . ' film başarıyla silindi.');
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

        Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->update([
                'is_watched' => true,
                'watched_at' => \DB::raw('COALESCE(watched_at, NOW())') // Zaten izlenmişse tarih değişmesin, değilse şuan olsun
            ]);

        return back()->with('success', count($request->movie_ids) . ' film izlendi olarak işaretlendi.');
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

        Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->update([
                'is_watched' => false,
                'watched_at' => null
            ]);

        return back()->with('success', count($request->movie_ids) . ' film izlenmedi olarak işaretlendi.');
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

        // syncWithoutDetaching ile mevcutları koruyarak yenilerini ekleriz
        $collection->movies()->syncWithoutDetaching($validMovieIds);

        return back()->with('success', count($validMovieIds) . ' film koleksiyona başarıyla eklendi.');
    }
}
