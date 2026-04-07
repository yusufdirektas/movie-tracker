<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Console\Command;

/**
 * 📚 ARTISAN KOMUTU: Eksik Cast Verilerini Doldur
 *
 * Mevcut filmlerde cast alanı boş olanları bulup TMDB'den çekerek doldurur.
 *
 * Çalıştırma:
 *   php artisan movies:fill-cast
 *   php artisan movies:fill-cast --limit=100  (sadece 100 film)
 */
class FillMissingCastData extends Command
{
    protected $signature = 'movies:fill-cast {--limit= : Maximum number of movies to process}';

    protected $description = 'Fill missing cast data for movies from TMDB API';

    public function handle(TmdbService $tmdb): int
    {
        $query = Movie::whereNotNull('tmdb_id')
            ->where('media_type', 'movie')
            ->where(function ($q) {
                $q->whereNull('cast')
                  ->orWhere('cast', '[]')
                  ->orWhere('cast', '');
            });

        $limit = $this->option('limit');
        if ($limit) {
            $query->limit((int) $limit);
        }

        $movies = $query->get();

        if ($movies->isEmpty()) {
            $this->info('✓ Tüm filmlerde cast verisi mevcut!');
            return Command::SUCCESS;
        }

        $this->info("🎬 {$movies->count()} film için cast verisi güncellenecek...");

        $bar = $this->output->createProgressBar($movies->count());
        $bar->start();

        $updated = 0;
        $failed = 0;
        $noCast = 0;

        foreach ($movies as $movie) {
            $response = $tmdb->getMovieDetails($movie->tmdb_id);

            if ($response?->successful()) {
                $data = $response->json();
                $castNames = collect($data['credits']['cast'] ?? [])
                    ->take(5)
                    ->pluck('name')
                    ->toArray();

                if (!empty($castNames)) {
                    $movie->update(['cast' => $castNames]);
                    $updated++;
                } else {
                    $noCast++; // API'de cast yok
                }
            } else {
                $failed++;
            }

            $bar->advance();

            // API rate limit'e takılmamak için kısa bir bekleme
            usleep(100000); // 0.1 saniye
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ {$updated} film güncellendi.");
        if ($noCast > 0) {
            $this->line("  {$noCast} filmde TMDB'de cast bilgisi yoktu.");
        }
        if ($failed > 0) {
            $this->warn("⚠ {$failed} film güncellenemedi (API hatası).");
        }

        return Command::SUCCESS;
    }
}
