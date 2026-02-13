<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MovieController;
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

// --- 1. ÖZEL FİLM ROTALARI (En Üste) ---

// Toplu İçe Aktarma Sayfası
Route::get('/movies/import-list', [MovieController::class, 'import'])
    ->middleware(['auth'])
    ->name('movies.import');

// Canlı Arama API'si (Import sayfasındaki "Analiz Et" butonu için şart)
Route::get('/movies/api-search', [MovieController::class, 'apiSearch'])
    ->middleware(['auth'])
    ->name('movies.api_search');


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
Route::resource('movies', MovieController::class)->middleware(['auth']);
