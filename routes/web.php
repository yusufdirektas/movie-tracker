<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\NowPlayingController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\PublicProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| ÖNEMLİ: Özel rotalar (/import-list gibi), her zaman genel
| resource rotalarından (/movies/{id} gibi) ÖNCE tanımlanmalıdır.
| Aksi takdirde Laravel 'import-list' kelimesini bir film ID'si sanar.
|
*/

// --- 1. HERKESE AÇIK (PUBLIC) ROTALAR ---
Route::get('/p/{token}', [PublicProfileController::class, 'showArchive'])->name('public.archive');
Route::get('/c/{token}', [PublicProfileController::class, 'showCollection'])->name('public.collection');

// --- 2. AYARLAR VE ÖZEL SAYFA ROTALARI ---

Route::middleware('auth')->group(function () {
    Route::post('/privacy/archive/toggle', [PrivacyController::class, 'toggleArchive'])->name('privacy.archive.toggle');
    Route::post('/privacy/collection/{collection}/toggle', [PrivacyController::class, 'toggleCollection'])->name('privacy.collection.toggle');
    Route::post('/privacy/regenerate-token', [PrivacyController::class, 'regenerateToken'])->name('privacy.regenerate-token');
});

// Toplu İçe Aktarma Sayfası
Route::get('/movies/import-list', [MovieController::class, 'import'])
    ->middleware(['auth'])
    ->name('movies.import');

// Canlı Arama API'si (Import sayfasındaki "Analiz Et" butonu için şart)
Route::get('/movies/api-search', [MovieController::class, 'apiSearch'])
    ->middleware(['auth', 'throttle:api-search'])
    ->name('movies.api_search');

// Sana Özel Öneriler Sayfası (YENİ EKLENEN ROTA)
Route::get('/movies/recommendations', [RecommendationController::class, 'index'])
    ->middleware(['auth'])
    ->name('movies.recommendations');
// İstatistikler Sayfası
Route::get('/movies/statistics', [StatisticsController::class, 'index'])
    ->middleware(['auth'])
    ->name('movies.statistics');
// Vizyondaki Filmler Sayfası
Route::get('/movies/now-playing', [NowPlayingController::class, 'index'])
    ->middleware(['auth'])
    ->name('movies.now_playing');
// Dışa Aktarma (Export) Sayfaları
Route::get('/movies/export/csv', [ExportController::class, 'exportCsv'])
    ->middleware(['auth'])
    ->name('movies.export.csv');
Route::get('/movies/export/json', [ExportController::class, 'exportJson'])
    ->middleware(['auth'])
    ->name('movies.export.json');

// --- 2. GENEL SAYFA ROTALARI ---

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('movies.index'); // Dashboard yerine direkt listeye git
})->middleware(['auth', 'verified'])->name('dashboard');


// --- 3. PROFİL VE OTURUM ROTALARI ---

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';


// --- 4. STANDART CRUD ROTALARI (En Alta) ---
// index, create, store, show, edit, update, destroy rotalarını otomatik oluşturur.
Route::get('/movies/watchlist', [WatchlistController::class, 'index'])->middleware(['auth'])->name('movies.watchlist');
Route::post('/movies', [MovieController::class, 'store'])->middleware(['auth', 'throttle:store-movie'])->name('movies.store');
Route::resource('movies', MovieController::class)->middleware(['auth'])->except(['store']);

// --- 5. KOLEKSİYON ROTALARI ---
Route::middleware('auth')->group(function () {
    Route::get('/collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::post('/collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::get('/collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
    Route::put('/collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
    Route::delete('/collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::post('/collections/{collection}/movies', [CollectionController::class, 'addMovie'])->name('collections.addMovie');
    Route::post('/collections/{collection}/movies/bulk', [CollectionController::class, 'addMovies'])->name('collections.addMovies');
    Route::delete('/collections/{collection}/movies/{movie}', [CollectionController::class, 'removeMovie'])->name('collections.removeMovie');
});

// --- 6. TOPLU İŞLEM ROTALARI (BULK ACTIONS) ---
Route::middleware('auth')->group(function () {
    Route::delete('/movies/bulk/delete', [\App\Http\Controllers\BulkActionController::class, 'delete'])->name('movies.bulk.delete');
    Route::post('/movies/bulk/watched', [\App\Http\Controllers\BulkActionController::class, 'markAsWatched'])->name('movies.bulk.watched');
    Route::post('/movies/bulk/unwatched', [\App\Http\Controllers\BulkActionController::class, 'markAsUnwatched'])->name('movies.bulk.unwatched');
    Route::post('/movies/bulk/collection', [\App\Http\Controllers\BulkActionController::class, 'addToCollection'])->name('movies.bulk.collection');
});
