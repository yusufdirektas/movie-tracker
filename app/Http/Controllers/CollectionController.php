<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    /**
     * Koleksiyon listesi
     */
    public function index()
    {
        $user = Auth::user();
        $collections = Collection::where('user_id', $user->id)
            ->withCount('movies')
            ->orderBy('name')
            ->get();

        return view('collections.index', compact('collections'));
    }

    /**
     * Koleksiyon detayı (içindeki filmler)
     */
    public function show(Collection $collection)
    {
        $this->authorize('view', $collection);

        $collection->load('movies');

        return view('collections.show', compact('collection'));
    }

    /**
     * Yeni koleksiyon oluştur
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        $user = Auth::user();

        $user->collections()->create([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? '#6366f1',
            'icon' => $request->icon ?? 'folder',
        ]);

        return back()->with('success', 'Koleksiyon oluşturuldu!');
    }

    /**
     * Koleksiyonu güncelle
     */
    public function update(Request $request, Collection $collection)
    {
        $this->authorize('update', $collection);

        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
        ]);

        $collection->update($request->only('name', 'description', 'color', 'icon'));

        return back()->with('success', 'Koleksiyon güncellendi!');
    }

    /**
     * Koleksiyonu sil
     */
    public function destroy(Collection $collection)
    {
        $this->authorize('delete', $collection);

        $collection->delete();

        return redirect()->route('collections.index')->with('success', 'Koleksiyon silindi!');
    }

    /**
     * Bir koleksiyona film ekle
     */
    public function addMovie(Request $request, Collection $collection)
    {
        $this->authorize('update', $collection);

        $request->validate([
            'movie_id' => 'required|integer|exists:movies,id',
        ]);

        // Zaten ekli mi?
        if ($collection->movies()->where('movie_id', $request->movie_id)->exists()) {
            return back()->with('error', 'Bu film zaten bu koleksiyonda!');
        }

        $collection->movies()->attach($request->movie_id);

        return back()->with('success', 'Film koleksiyona eklendi!');
    }

    /**
     * Bir koleksiyona birden fazla film ekle (Toplu ekleme)
     */
    public function addMovies(Request $request, Collection $collection)
    {
        $this->authorize('update', $collection);

        $request->validate([
            'movie_ids' => 'required|array',
            'movie_ids.*' => 'integer|exists:movies,id',
        ]);

        // Zaten ekli olanları filtrele
        $existingIds = $collection->movies()->pluck('movies.id')->toArray();
        $newIds = array_diff($request->movie_ids, $existingIds);

        if (!empty($newIds)) {
            $collection->movies()->attach($newIds);
        }

        $count = count($newIds);
        return back()->with('success', "{$count} film koleksiyona eklendi!");
    }

    /**
     * Bir koleksiyondan film çıkar
     */
    public function removeMovie(Collection $collection, Movie $movie)
    {
        $this->authorize('update', $collection);

        $collection->movies()->detach($movie->id);

        return back()->with('success', 'Film koleksiyondan çıkarıldı!');
    }
}
