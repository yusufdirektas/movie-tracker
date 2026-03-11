<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Kullanıcının izlediği filmleri CSV formatında dışa aktarır.
     */
    public function exportCsv()
    {
        $user = Auth::user();
        $movies = $user->movies()->watched()->orderBy('watched_at', 'desc')->get();

        $filename = "film_arsivim_" . date('Y-m-d_H-i-s') . ".csv";

        $response = new StreamedResponse(function () use ($movies) {
            $handle = fopen('php://output', 'w');
            
            // UTF-8 BOM ekle (Excel'de Türkçe karakterlerin düzgün görünmesi için kritik)
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Başlık satırı
            fputcsv($handle, [
                'Film Adı',
                'Yönetmen',
                'Türler',
                'Çıkış Yılı',
                'Süre (Dk)',
                'TMDB Puanı',
                'Kişisel Puan',
                'İzlenme Tarihi',
                'TMDB Linki'
            ], ';'); // Noktalı virgül kullanarak Türkçe Excel ile uyumlu yapıyoruz

            // Veri satırları
            foreach ($movies as $movie) {
                $genres = is_array($movie->genres) ? implode(', ', $movie->genres) : '';
                $releaseYear = $movie->release_date ? substr($movie->release_date, 0, 4) : '';
                $watchedAt = $movie->watched_at ? $movie->watched_at->format('d.m.Y') : '';
                $tmdbLink = $movie->tmdb_id ? "https://www.themoviedb.org/movie/{$movie->tmdb_id}" : '';

                fputcsv($handle, [
                    $movie->title,
                    $movie->director ?? 'Bilinmiyor',
                    $genres,
                    $releaseYear,
                    $movie->runtime,
                    $movie->rating,
                    $movie->personal_rating,
                    $watchedAt,
                    $tmdbLink
                ], ';');
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    /**
     * Kullanıcının izlediği filmleri JSON formatında dışa aktarır.
     */
    public function exportJson()
    {
        $user = Auth::user();
        $movies = $user->movies()->watched()->orderBy('watched_at', 'desc')->get();

        $exportData = $movies->map(function ($movie) {
            return [
                'title' => $movie->title,
                'director' => $movie->director,
                'genres' => $movie->genres,
                'release_date' => $movie->release_date,
                'runtime_minutes' => $movie->runtime,
                'tmdb_rating' => $movie->rating,
                'personal_rating' => $movie->personal_rating,
                'watched_at' => $movie->watched_at ? $movie->watched_at->format('Y-m-d') : null,
                'tmdb_id' => $movie->tmdb_id,
            ];
        });

        $filename = "film_arsivim_" . date('Y-m-d_H-i-s') . ".json";

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
