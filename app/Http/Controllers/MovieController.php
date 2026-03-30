<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\StartImportRequest;
use App\Http\Requests\UpdateMovieRequest;
use App\Jobs\ProcessImportItemJob;
use App\Models\ImportBatch;
use App\Models\ImportItem;
use App\Models\Movie;
use App\Models\User;
use App\Services\TmdbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    protected $tmdb;

    public function __construct(TmdbService $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    // 1. ODA: SADECE İZLENENLER (Film Arşivim)
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (blank($user->share_token)) {
            $user->forceFill(['share_token' => (string) Str::uuid()])->save();
            $user->refresh();
        }

        // İSTATİSTİKLERİ SAYFALANDIRMADAN BAĞIMSIZ ÇEKİYORUZ
        $totalMovies = $user->movies()->count();
        $baseWatchedQuery = clone $user->movies()->watched();

        $watchedCount = $baseWatchedQuery->count();
        $totalMinutes = $baseWatchedQuery->sum('runtime');
        $totalHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;
        // `clone` etmezsek yukarıdaki sum() ve count() sonrası query bozulabilir. Zaten modelin yeni bir query nesnesi oluşturduğundan yukarıda sıkıntı yok ama en doğrusu ayrı çalışmak.
        $highestRated = $user->movies()
            ->watched()
            ->orderByDesc('rating')
            ->first(['id', 'title', 'poster_path', 'rating']);

        // ---------------------------------------------------------------------
        // 📚 REFACTORING (KOD İYİLEŞTİRME) SONRASI:
        // Eskiden burada iç içe geçmiş bir sürü if bloğu ve sorgu vardı.
        // Şimdi tüm karmaşıklık Model (Movie.php) içindeki Scope'lara taşındı.
        // Kod bir hikaye okur gibi yukarıdan aşağıya akıyor.
        // ---------------------------------------------------------------------

        // ---------------------------------------------------------------------
        // 📚 FİLTRE PARAMETRELERİNİ ALMA (Request Input)
        //
        // request->input('key', 'default') → URL'den parametre alır
        // Örn: /movies?search=matrix&year_from=1999 → $search='matrix', $yearFrom=1999
        //
        // mb_strtolower() → Türkçe karakterleri de küçük harfe çevirir (İ→i, Ş→ş)
        // ---------------------------------------------------------------------

        $filter = $request->input('filter', 'all');
        $search = mb_strtolower((string) $request->input('search', ''), 'UTF-8');
        $genre = $request->input('genre');
        $sort = $request->input('sort', 'updated_at');

        // 🆕 GELİŞMİŞ ARAMA PARAMETRELERİ
        $yearFrom = $request->input('year_from');
        $yearTo = $request->input('year_to');
        $runtimeMin = $request->input('runtime_min');
        $runtimeMax = $request->input('runtime_max');
        $ratingMin = $request->input('rating_min');
        $director = $request->input('director');
        $mediaType = $request->input('media_type');

        $allowedSorts = [
            'updated_at' => 'desc',
            'title' => 'asc',
            'rating' => 'desc',
            'personal_rating' => 'desc',
            'release_date' => 'desc',
            'runtime' => 'desc',
        ];

        /**
         * 📚 SCOPE ZİNCİRLEME (Method Chaining)
         *
         * Laravel Eloquent'te scope'lar zincirleme çağrılabilir.
         * Her scope, query builder nesnesini döndürür.
         * Boş değerler için scope içinde kontrol yapılır, böylece
         * controller'da if-else karmaşası olmaz.
         *
         * Not: Index grid görünümünde koleksiyon ilişkisi kullanılmadığı için
         * burada eager-loading yapılmıyor; gereksiz sorgu/memory yükü engelleniyor.
         */
        $query = $user->movies()
            ->select([
                'id',
                'user_id',
                'title',
                'poster_path',
                'rating',
                'personal_rating',
                'director',
                'runtime',
                'release_date',
                'is_watched',
                'watched_at',
                'updated_at',
            ])
            ->watched()
            ->searchByTitle($search)
            ->filterByGenre($genre)
            ->filterByYearRange($yearFrom, $yearTo)      // 🆕 Yıl filtresi
            ->filterByRuntime($runtimeMin, $runtimeMax)  // 🆕 Süre filtresi
            ->filterByRating($ratingMin)                 // 🆕 Puan filtresi
            ->filterByDirector($director)               // 🆕 Yönetmen filtresi
            ->filterByMediaType($mediaType)             // 🆕 Film/Dizi filtresi
            ->applySort($sort, $allowedSorts);

        if ($filter === 'favorites') {
            $query->where('personal_rating', '>=', 4);
        }

        $movies = $query->paginate(20)->withQueryString();

        // Türleri yine Model üzerinden alıyoruz
        $availableGenres = Movie::getAvailableGenres($user->id, true);

        // 🆕 Yönetmenleri al (Gelişmiş arama için)
        $availableDirectors = Movie::getAvailableDirectors($user->id, true);

        // Koleksiyonlar (Toplu işlem toolbar'ı için)
        $collections = $user->collections()->orderBy('name')->get();

        // 🆕 Gelişmiş filtreleri view'a gönder (form değerlerini korumak için)
        $advancedFilters = compact(
            'yearFrom', 'yearTo', 'runtimeMin', 'runtimeMax',
            'ratingMin', 'director', 'mediaType'
        );

        if ($request->ajax()) {
            return view('movies.partials._grid', compact('movies'));
        }

        return view('movies.index', compact(
            'movies', 'search', 'filter', 'genre', 'availableGenres', 'sort',
            'totalMovies', 'watchedCount', 'totalHours', 'remainingMinutes', 'highestRated',
            'collections', 'availableDirectors', 'advancedFilters'
        ));
    }

    public function create()
    {
        // TMDB tür listesini al (gelişmiş arama dropdown'u için)
        $tmdbGenres = $this->tmdb->getGenres();

        return view('movies.create', compact('tmdbGenres'));
    }

    /**
     * 📚 API ARAMA ENDPOINT'İ
     *
     * Bu endpoint 3 farklı modda çalışır:
     *
     * 1. Normal Arama (?query=batman)
     *    → Basit film adı araması, create sayfası için
     *
     * 2. Akıllı Arama (?query=batman&smart=1)
     *    → Yazım hatası toleranslı, import sayfası için
     *
     * 3. Gelişmiş Arama (?discover=1&year_from=2020&min_rating=7)
     *    → TMDB Discover API ile filtreleme
     */
    public function apiSearch(Request $request)
    {
        // 🆕 Gelişmiş arama modu (Discover API)
        if ($request->boolean('discover')) {
            return $this->handleDiscoverSearch($request);
        }

        $query = mb_strtolower((string) $request->input('query', ''), 'UTF-8');
        if (! $query) {
            return response()->json([]);
        }

        // Import sayfası smart=1 parametresi gönderir → yazım hatası toleranslı arama
        if ($request->boolean('smart')) {
            $result = $this->tmdb->smartSearch($query);

            return response()->json([
                'results' => collect($result['results'])->take(6),
                'corrected' => $result['corrected'],
                'corrected_query' => $result['corrected_query'],
                'suggestions' => collect($result['suggestions'])->take(5),
            ]);
        }

        // Normal arama (create sayfasındaki canlı arama için)
        $response = $this->tmdb->searchMovies($query);

        if ($response?->successful()) {
            return response()->json(collect($response->json()['results'] ?? [])->take(6));
        }

        return response()->json([]);
    }

    /**
     * 📚 GELİŞMİŞ ARAMA İŞLEYİCİSİ (Discover API)
     *
     * TMDB Discover API'yi kullanarak filtrelenmiş sonuçlar döner.
     * Query varsa search + client-side filter, yoksa pure discover.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleDiscoverSearch(Request $request)
    {
        $filters = [
            'query' => $request->input('query'),
            'year' => $request->input('year'),
            'year_from' => $request->input('year_from'),
            'year_to' => $request->input('year_to'),
            'genre' => $request->input('genre'),
            'min_rating' => $request->input('min_rating'),
            'runtime_min' => $request->input('runtime_min'),
            'runtime_max' => $request->input('runtime_max'),
            'sort_by' => $request->input('sort_by', 'popularity.desc'),
            'page' => $request->input('page', 1), // 🆕 Sayfa numarası
        ];

        // Boş değerleri temizle (page hariç)
        $page = $filters['page'];
        $filters = array_filter($filters, fn ($v) => $v !== null && $v !== '');
        $filters['page'] = $page;

        // En az bir filtre olmalı (page hariç)
        $filtersWithoutPage = array_diff_key($filters, ['page' => true]);
        if (empty($filtersWithoutPage)) {
            return response()->json(['results' => [], 'page' => 1, 'total_pages' => 0]);
        }

        $response = $this->tmdb->discoverMovies($filters);

        if ($response?->successful()) {
            $data = $response->json();
            $results = collect($data['results'] ?? []);

            // Query varsa client-side filtreleme yap
            // (TMDB search API yıl dışında filtre desteklemiyor)
            if (! empty($filters['query'])) {
                // Yıl filtresi
                if (! empty($filters['year_from'])) {
                    $results = $results->filter(function ($movie) use ($filters) {
                        $year = substr($movie['release_date'] ?? '', 0, 4);

                        return $year >= $filters['year_from'];
                    });
                }
                if (! empty($filters['year_to'])) {
                    $results = $results->filter(function ($movie) use ($filters) {
                        $year = substr($movie['release_date'] ?? '', 0, 4);

                        return $year <= $filters['year_to'];
                    });
                }

                // Puan filtresi
                if (! empty($filters['min_rating'])) {
                    $results = $results->filter(function ($movie) use ($filters) {
                        return ($movie['vote_average'] ?? 0) >= $filters['min_rating'];
                    });
                }

                // Tür filtresi
                if (! empty($filters['genre'])) {
                    $results = $results->filter(function ($movie) use ($filters) {
                        return in_array((int) $filters['genre'], $movie['genre_ids'] ?? []);
                    });
                }
            }

            /**
             * 📚 SAYFALAMA BİLGİSİ
             *
             * TMDB API her yanıtta şunları döner:
             * - page: Mevcut sayfa numarası
             * - total_pages: Toplam sayfa sayısı
             * - total_results: Toplam sonuç sayısı
             *
             * Frontend bu bilgiyi kullanarak "Daha fazla yükle" butonunu
             * gösterip göstermeyeceğine karar verir.
             */
            return response()->json([
                'results' => $results->values(),
                'page' => $data['page'] ?? 1,
                'total_pages' => min($data['total_pages'] ?? 1, 500), // TMDB max 500 sayfa
                'total_results' => $data['total_results'] ?? 0,
            ]);
        }

        return response()->json(['results' => [], 'page' => 1, 'total_pages' => 0]);
    }

    public function store(StoreMovieRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $mediaType = $request->input('media_type', 'movie');
        $alreadyExists = $user->movies()
            ->where('tmdb_id', $request->tmdb_id)
            ->where('media_type', $mediaType)
            ->exists();

        if ($alreadyExists) {
            Log::info('movie.store.duplicate', [
                'user_id' => $user->id,
                'tmdb_id' => $request->tmdb_id,
                'media_type' => $mediaType,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Bu içerik zaten arşivinde mevcut!'], 400);
            }

            return back()
                ->with('error', 'Bu içerik zaten arşivinde mevcut!')
                ->with('error_action', 'Aramaya dönüp farklı bir içerik seçebilirsin.');
        }

        // media_type'a göre film veya dizi detayını çek
        if ($mediaType === 'tv') {
            $response = $this->tmdb->getTvDetails($request->tmdb_id);
        } else {
            $response = $this->tmdb->getMovieDetails($request->tmdb_id);
        }

        if ($response?->successful()) {
            $data = $response->json();
            $isWatched = $request->boolean('is_watched');
            $genres = collect($data['genres'] ?? [])->pluck('name')->toArray();

            if ($mediaType === 'tv') {
                // Dizi: created_by → yönetmen, name → başlık, episode_run_time → süre
                $creator = collect($data['created_by'] ?? [])->first()['name']
                    ?? collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name']
                    ?? 'Bilinmiyor';
                $runtime = $data['episode_run_time'][0] ?? $data['last_episode_to_air']['runtime'] ?? null;

                $user->movies()->create([
                    'tmdb_id' => $data['id'],
                    'media_type' => 'tv',
                    'title' => $data['name'] ?? $data['original_name'],
                    'director' => $creator,
                    'genres' => $genres,
                    'poster_path' => $data['poster_path'],
                    'rating' => $data['vote_average'],
                    'runtime' => $runtime,
                    'overview' => empty($data['overview']) ? null : $data['overview'],
                    'release_date' => $data['first_air_date'] ?? null,
                    'is_watched' => $isWatched,
                    'watched_at' => $isWatched ? now() : null,
                ]);
            } else {
                // Film: credits.crew → yönetmen, title → başlık
                $director = collect($data['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';

                $user->movies()->create([
                    'tmdb_id' => $data['id'],
                    'media_type' => 'movie',
                    'title' => $data['title'],
                    'director' => $director,
                    'genres' => $genres,
                    'poster_path' => $data['poster_path'],
                    'rating' => $data['vote_average'],
                    'runtime' => $data['runtime'],
                    'overview' => empty($data['overview']) ? null : $data['overview'],
                    'release_date' => $data['release_date'],
                    'is_watched' => $isWatched,
                    'watched_at' => $isWatched ? now() : null,
                ]);
            }

            $label = $mediaType === 'tv' ? 'Dizi' : 'Film';
            $message = $isWatched ? "{$label} listeye eklendi!" : "{$label} izleneceklere eklendi!";

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()
                ->route($isWatched ? 'movies.index' : 'movies.watchlist')
                ->with('success', $message);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'Bilgiler alınamadı.'], 500);
        }

        Log::warning('movie.store.tmdb_fetch_failed', [
            'user_id' => $user->id,
            'tmdb_id' => $request->tmdb_id,
            'media_type' => $mediaType,
        ]);

        return back()
            ->with('error', 'İçerik bilgileri şu anda alınamadı.')
            ->with('error_action', 'Birkaç saniye sonra tekrar deneyebilirsin.');
    }

    /**
     * 📚 ROUTE MODEL BINDING:
     * URL'de /movies/5 yazıldığında Laravel otomatik olarak
     * Movie::findOrFail(5) çalıştırır ve $movie değişkenine atar.
     * Eğer film bulunamazsa otomatik 404 döner.
     *
     * 📚 POLICY AUTHORIZATION:
     * $this->authorize('view', $movie) → MoviePolicy::view() metodunu çağırır.
     * Kullanıcı filmin sahibi değilse 403 Forbidden döner.
     */
    public function show(Movie $movie)
    {
        $this->authorize('view', $movie);

        // Bu filmin TMDB ID'si varsa benzer film önerilerini çek (24 saat cache)
        $similarMovies = [];
        if ($movie->tmdb_id) {
            $cacheKey = 'movie_similar_'.$movie->tmdb_id;
            $similarMovies = Cache::remember($cacheKey, now()->addHours(24), function () use ($movie) {
                $response = $this->tmdb->getSimilar($movie->tmdb_id);

                return $response?->successful() ? ($response->json()['results'] ?? []) : [];
            });
            $similarMovies = collect($similarMovies)->take(6);
        }
        // Kullanıcının koleksiyonlarını al (Koleksiyona Ekle dropdown'u için)
        $collections = Auth::user()->collections()->orderBy('name')->get();
        $movieCollectionIds = $movie->collections()->pluck('collections.id')->toArray();

        return view('movies.show', compact('movie', 'similarMovies', 'collections', 'movieCollectionIds'));
    }

    public function update(UpdateMovieRequest $request, Movie $movie)
    {
        $this->authorize('update', $movie);

        if ($request->has('personal_note')) {
            $movie->update([
                'personal_note' => $request->input('personal_note'),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Not kaydedildi.']);
            }

            return back()->with('success', 'Kişisel not kaydedildi.');
        }

        if ($request->has('personal_rating')) {
            $movie->update([
                'personal_rating' => $request->personal_rating,
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Puan kaydedildi.']);
            }

            return back();
        }

        if ($request->has('watch_priority')) {
            $movie->update([
                'watch_priority' => (int) $request->input('watch_priority'),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Öncelik güncellendi.']);
            }

            return back()->with('success', 'İzleme önceliği güncellendi.');
        }

        $newWatchedStatus = ! $movie->is_watched;
        $movie->update([
            'is_watched' => $newWatchedStatus,
            'watched_at' => $newWatchedStatus ? ($movie->watched_at ?? now()) : null,
        ]);

        return back()->with('success', 'Film durumu güncellendi.');
    }

    public function destroy(Movie $movie)
    {
        $this->authorize('delete', $movie);
        $movie->delete();

        return redirect()->route('movies.index')->with('success', 'Film silindi.');
    }

    public function import()
    {
        return view('movies.import', [
            'latestBatch' => Auth::user()->importBatches()->latest()->first(),
        ]);
    }

    public function importHistory()
    {
        /** @var User $user */
        $user = Auth::user();

        $batches = $user->importBatches()
            ->latest()
            ->paginate(10);

        return view('movies.import-history', compact('batches'));
    }

    public function startImport(StartImportRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $lines = collect(preg_split('/\r\n|\r|\n/', (string) $request->input('raw_text')))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values();

        if ($lines->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aktarim listesi bos olamaz.',
            ], 422);
        }

        if ($lines->count() > 1000) {
            return response()->json([
                'success' => false,
                'message' => 'Tek importta en fazla 1000 satir destekleniyor.',
            ], 422);
        }

        $batch = ImportBatch::query()->create([
            'user_id' => $user->id,
            'status' => 'queued',
            'is_watched' => $request->boolean('is_watched', true),
            'total_items' => $lines->count(),
        ]);

        $now = now();
        $itemsPayload = $lines->map(fn ($line, $index) => [
            'import_batch_id' => $batch->id,
            'line_number' => $index + 1,
            'original_query' => $line,
            'normalized_query' => mb_strtolower($line, 'UTF-8'),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        ImportItem::query()->insert($itemsPayload);

        $itemIds = ImportItem::query()
            ->where('import_batch_id', $batch->id)
            ->orderBy('line_number')
            ->pluck('id');

        foreach ($itemIds as $itemId) {
            ProcessImportItemJob::dispatch((int) $itemId)->onQueue('imports');
        }

        return response()->json([
            'success' => true,
            'batch_id' => $batch->id,
        ], 202);
    }

    public function importStatus(ImportBatch $batch)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($batch->user_id === $user->id, 403);

        $items = $batch->items()
            ->orderBy('line_number')
            ->get([
                'id',
                'line_number',
                'original_query',
                'resolved_title',
                'status',
                'error_message',
                'was_corrected',
                'corrected_query',
                'tmdb_id',
                'media_type',
            ]);

        return response()->json([
            'batch' => [
                'id' => $batch->id,
                'status' => $batch->status,
                'total_items' => $batch->total_items,
                'processed_items' => $batch->processed_items,
                'success_items' => $batch->success_items,
                'duplicate_items' => $batch->duplicate_items,
                'not_found_items' => $batch->not_found_items,
                'error_items' => $batch->error_items,
                'skipped_items' => $batch->skipped_items,
                'started_at' => $batch->started_at,
                'finished_at' => $batch->finished_at,
            ],
            'items' => $items,
        ]);
    }

    public function retryFailedItems(ImportBatch $batch)
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($batch->user_id === $user->id, 403);

        // Get failed items (error + not_found)
        $failedItems = $batch->items()
            ->whereIn('status', ['error', 'not_found'])
            ->get();

        if ($failedItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Yeniden denenecek hatalı öğe bulunamadı.',
            ], 422);
        }

        // Reset failed items to pending
        $batch->items()
            ->whereIn('status', ['error', 'not_found'])
            ->update([
                'status' => 'pending',
                'error_message' => null,
                'resolved_title' => null,
                'tmdb_id' => null,
                'media_type' => null,
                'processed_at' => null,
            ]);

        // Update batch counters
        $retryCount = $failedItems->count();
        $batch->update([
            'status' => 'processing',
            'processed_items' => $batch->processed_items - $retryCount,
            'error_items' => 0,
            'not_found_items' => 0,
            'finished_at' => null,
        ]);

        // Re-dispatch jobs for failed items
        foreach ($failedItems as $item) {
            ProcessImportItemJob::dispatch((int) $item->id)->onQueue('imports');
        }

        return response()->json([
            'success' => true,
            'message' => "{$retryCount} öğe yeniden kuyruğa alındı.",
            'retry_count' => $retryCount,
        ]);
    }
}
