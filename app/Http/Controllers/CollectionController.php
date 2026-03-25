<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
     *
     * 📚 EAGER LOADING AÇIKLAMASI:
     * load() vs with() farkı:
     * - with(): Query BAŞINDA kullanılır → Collection::with('movies')->find($id)
     * - load(): Model SONRASINDA kullanılır → $collection->load('movies')
     *
     * Route Model Binding ($collection parametresi) kullandığımız için
     * model zaten yüklenmiş durumda. Bu yüzden load() kullanıyoruz.
     */
    public function show(Collection $collection)
    {
        $this->authorize('view', $collection);

        if ($collection->is_public && blank($collection->share_token)) {
            $collection->forceFill(['share_token' => (string) Str::uuid()])->save();
            $collection->refresh();
        }

        // Filmleri user ilişkisiyle birlikte yükle (ileride kullanıcı adı göstermek istersek)
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

        // Güvenlik: Filmin gerçekten bu kullanıcıya ait olduğunu doğrula (IDOR koruması)
        $movie = \App\Models\Movie::where('id', $request->movie_id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // Zaten ekli mi?
        if ($collection->movies()->where('movie_id', $movie->id)->exists()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bu film zaten bu koleksiyonda!',
                ], 409);
            }

            return back()->with('error', 'Bu film zaten bu koleksiyonda!');
        }

        $collection->movies()->attach($movie->id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Film koleksiyona eklendi!',
                'collection_id' => $collection->id,
                'movie_id' => $movie->id,
            ]);
        }

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

        // Güvenlik: Sadece kullanıcının kendi filmlerini kabul et (IDOR koruması)
        $validMovieIds = \App\Models\Movie::whereIn('id', $request->movie_ids)
            ->where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();

        // Zaten ekli olanları filtrele
        $existingIds = $collection->movies()->pluck('movies.id')->toArray();
        $newIds = array_diff($validMovieIds, $existingIds);

        if (! empty($newIds)) {
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

        // Güvenlik: Filmin bu kullanıcıya ait olduğunu doğrula (IDOR koruması)
        if ($movie->user_id !== Auth::id()) {
            abort(403);
        }

        $collection->movies()->detach($movie->id);

        return back()->with('success', 'Film koleksiyondan çıkarıldı!');
    }
}
