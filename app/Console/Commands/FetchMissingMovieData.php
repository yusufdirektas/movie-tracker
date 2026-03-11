<?php

namespace App\Console\Commands;

use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchMissingMovieData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:fetch-missing-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eksik tür (genres) veya yönetmen (director) bilgisi olan filmleri TMDB\'den günceller.';

    /**
     * Execute the console command.
     */
    public function handle(TmdbService $tmdbService)
    {
        $this->info('Eksik film verilerini güncelleme işlemi başlıyor...');

        // Türleri (genres) veya yönetmeni boş olan tüm filmleri getir
        $movies = Movie::whereNull('genres')
            ->orWhereNull('director')
            ->orWhere('director', 'Bilinmiyor')
            ->get();

        if ($movies->isEmpty()) {
            $this->info('Güncellenecek eksik veri kalmadı. Her şey tam!');
            return;
        }

        $this->info($movies->count() . " adet film güncellenecek.");
        $bar = $this->output->createProgressBar($movies->count());
        $bar->start();

        foreach ($movies as $movie) {
            // TMDB araması yap
            $response = $tmdbService->searchMovie($movie->title, substr($movie->release_date, 0, 4));
            
            if ($response && $response->successful() && !empty($response->json()['results'])) {
                $tmdbMovie = $response->json()['results'][0]; // İlk sonuç
                $tmdbId = $tmdbMovie['id'];

                // 2. Adım: Film Detaylarını Çek (Tür İsimleri ve Yönetmen için)
                $detailsResponse = $tmdbService->getMovieDetails($tmdbId);
                
                if ($detailsResponse && $detailsResponse->successful()) {
                    $details = $detailsResponse->json();
                    
                    // Türleri ayıkla
                    $genres = [];
                    if (isset($details['genres']) && is_array($details['genres'])) {
                        foreach ($details['genres'] as $genre) {
                            $genres[] = $genre['name'];
                        }
                    }

                    // Yönetmeni ayıkla
                    $director = 'Bilinmiyor';
                    if (isset($details['credits']['crew']) && is_array($details['credits']['crew'])) {
                        foreach ($details['credits']['crew'] as $crewMember) {
                            if ($crewMember['job'] === 'Director') {
                                $director = $crewMember['name'];
                                break;
                            }
                        }
                    }

                    // Güncelle
                    try {
                        $movie->update([
                            'tmdb_id' => $tmdbId,
                            'genres' => !empty($genres) ? $genres : null,
                            'director' => $director,
                            'poster_path' => $details['poster_path'] ?? $movie->poster_path, // Eksikse posteri de tamamla
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("TMDB kaydetme hatası (Muhtemelen duplicate TMDB ID): " . $movie->title);
                        // Eğer tmdb_id unique hatası veriyorsa sadece tür/yönetmen kaydedebiliriz:
                        // Ancak Eloquent modelinde tmdb_id 'dirty' olarak kaldığı için önce refresh yapmalıyız.
                        $movie->refresh();
                        $movie->update([
                            'genres' => !empty($genres) ? $genres : null,
                            'director' => $director,
                            'poster_path' => $details['poster_path'] ?? $movie->poster_path, // Eksikse posteri de tamamla
                        ]);
                    }
                }
            } else {
                Log::warning("TMDB'de bulunamadı: " . $movie->title);
            }

            $bar->advance();
            
            // TMDB API limitlerine takılmamak için (saniyede ~40 limitine karşı küçük bir bekleme)
            usleep(200000); // 0.2 saniye
        }

        $bar->finish();
        
        $this->newLine();
        $this->info('Güncelleme işlemi tamamlandı!');
    }
}
