<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchMissingMovieData extends Command
{
    protected $signature = 'movies:fetch-missing-data';

    protected $description = 'Eksik tür (genres), yönetmen (director) veya oyuncu (cast) bilgisi olan filmleri TMDB\'den günceller.';

    public function handle(TmdbService $tmdbService)
    {
        $this->info('Eksik film verilerini güncelleme işlemi başlıyor...');

        /**
         * 📚 SADECE CAST'İ OLMAYAN FİLMLERİ ÇEK
         *
         * Dün komut searchMovie() + getMovieDetails() yapıyordu.
         * AMA filmlerimizin zaten tmdb_id'si var!
         * Gereksiz arama = gereksiz API çağrısı + hata riski.
         *
         * Yeni yaklaşım: tmdb_id'si olan ama cast'i olmayan filmleri
         * doğrudan getMovieDetails(tmdb_id) ile güncelle.
         */
        $movies = Movie::withoutEvents(function () {
            return Movie::whereNotNull('tmdb_id')
                ->where(function ($query) {
                    $query->whereNull('cast')
                        ->orWhereNull('genres')
                        ->orWhereNull('director')
                        ->orWhere('director', 'Bilinmiyor');
                })
                ->get(['id', 'tmdb_id', 'title', 'media_type', 'genres', 'director', 'cast', 'poster_path']);
        });

        if ($movies->isEmpty()) {
            $this->info('Güncellenecek eksik veri kalmadı. Her şey tam!');
            return;
        }

        $this->info($movies->count() . " adet film güncellenecek.");
        $bar = $this->output->createProgressBar($movies->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($movies as $movie) {
            try {
                /**
                 * 📚 DOĞRUDAN tmdb_id İLE DETAY ÇEK
                 *
                 * Eski yöntem: searchMovie(title) → sonuç al → getMovieDetails(id)
                 *   Problem: 2 API çağrısı, arama yanlış eşleşebilir
                 *
                 * Yeni yöntem: getMovieDetails(tmdb_id) → 1 API çağrısı, kesin sonuç
                 *   tmdb_id zaten veritabanında kayıtlı, aramanın anlamı yok!
                 */
                $detailsResponse = $tmdbService->getMovieDetails($movie->tmdb_id);

                if (! $detailsResponse?->successful()) {
                    $failed++;
                    $bar->advance();
                    usleep(300000);
                    continue;
                }

                $details = $detailsResponse->json();

                // Türleri ayıkla
                $genres = collect($details['genres'] ?? [])->pluck('name')->toArray();

                // Yönetmeni ayıkla
                $director = collect($details['credits']['crew'] ?? [])
                    ->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';

                // Oyuncu kadrosunu ayıkla (ilk 5 baş rol)
                $castNames = collect($details['credits']['cast'] ?? [])
                    ->take(5)
                    ->pluck('name')
                    ->values()
                    ->toArray();

                // Güncelle (sadece boş alanları doldur)
                $updateData = [];

                if (empty($movie->genres)) {
                    $updateData['genres'] = !empty($genres) ? $genres : null;
                }
                if (empty($movie->director) || $movie->director === 'Bilinmiyor') {
                    $updateData['director'] = $director;
                }
                if (empty($movie->cast)) {
                    $updateData['cast'] = !empty($castNames) ? $castNames : null;
                }
                if (empty($movie->poster_path) && !empty($details['poster_path'])) {
                    $updateData['poster_path'] = $details['poster_path'];
                }

                if (!empty($updateData)) {
                    // JSON alanlarını encode et (Query builder'da Eloquent cast'ler çalışmaz)
                    if (isset($updateData['cast']) && is_array($updateData['cast'])) {
                        $updateData['cast'] = json_encode($updateData['cast']);
                    }
                    if (isset($updateData['genres']) && is_array($updateData['genres'])) {
                        $updateData['genres'] = json_encode($updateData['genres']);
                    }
                    // Query builder kullan (model events ve lazy loading sorununu bypass eder)
                    Movie::where('id', $movie->id)->update($updateData);
                    $updated++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::warning("TMDB güncelleme hatası [{$movie->tmdb_id}]: " . $e->getMessage());
            }

            $bar->advance();

            // TMDB API rate limit: ~40 req/s → 250ms bekleme güvenli
            usleep(250000);
        }

        $bar->finish();

        $this->newLine();
        $this->info("✓ Güncelleme tamamlandı! {$updated} güncellendi, {$failed} başarısız.");
    }
}
