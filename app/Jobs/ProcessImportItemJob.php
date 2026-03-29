<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\ImportItem;
use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcessImportItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 45;

    public function __construct(public int $importItemId)
    {
        $this->onQueue('imports');
    }

    public function handle(TmdbService $tmdb): void
    {
        /** @var ImportItem|null $item */
        $item = ImportItem::query()->with('batch.user')->find($this->importItemId);
        if (! $item) {
            return;
        }

        /** @var ImportBatch $batch */
        $batch = $item->batch;
        if (! $batch || $batch->status === 'cancelled') {
            return;
        }

        if (! in_array($item->status, ['pending', 'error'], true)) {
            return;
        }

        $item->update(['status' => 'processing']);
        if (! $batch->started_at) {
            $batch->update(['started_at' => now(), 'status' => 'processing']);
        }

        $searchResult = $tmdb->smartSearch($item->original_query);
        $best = $searchResult['results'][0] ?? null;

        if (! $best) {
            $this->finalizeItem($batch->id, $item->id, 'not_found', [
                'error_message' => 'TMDB sonuc bulunamadi.',
                'processed_at' => now(),
            ]);
            return;
        }

        $mediaType = ($best['media_type'] ?? 'movie') === 'tv' ? 'tv' : 'movie';
        $tmdbId = (int) ($best['id'] ?? 0);

        if ($tmdbId <= 0) {
            $this->finalizeItem($batch->id, $item->id, 'error', [
                'error_message' => 'Gecersiz TMDB kimligi.',
                'processed_at' => now(),
            ]);
            return;
        }

        $alreadyExists = Movie::query()
            ->where('user_id', $batch->user_id)
            ->where('tmdb_id', $tmdbId)
            ->where('media_type', $mediaType)
            ->exists();

        if ($alreadyExists) {
            $this->finalizeItem($batch->id, $item->id, 'duplicate', [
                'tmdb_id' => $tmdbId,
                'media_type' => $mediaType,
                'resolved_title' => $best['title'] ?? $best['name'] ?? $item->original_query,
                'was_corrected' => (bool) ($searchResult['corrected'] ?? false),
                'corrected_query' => $searchResult['corrected_query'] ?? null,
                'processed_at' => now(),
            ]);
            return;
        }

        $cacheKey = "import:tmdb:detail:{$mediaType}:{$tmdbId}";
        $detail = Cache::remember($cacheKey, now()->addHours(24), function () use ($tmdb, $mediaType, $tmdbId) {
            $response = $mediaType === 'tv' ? $tmdb->getTvDetails($tmdbId) : $tmdb->getMovieDetails($tmdbId);
            if (! $response?->successful()) {
                return null;
            }

            return $response->json();
        });

        if (! $detail) {
            $this->finalizeItem($batch->id, $item->id, 'error', [
                'error_message' => 'TMDB detay verisi alinamadi.',
                'tmdb_id' => $tmdbId,
                'media_type' => $mediaType,
                'processed_at' => now(),
            ]);
            return;
        }

        $genres = collect($detail['genres'] ?? [])->pluck('name')->values()->all();
        $isWatched = (bool) $batch->is_watched;

        if ($mediaType === 'tv') {
            $title = $detail['name'] ?? $detail['original_name'] ?? ($best['name'] ?? $item->original_query);
            $director = collect($detail['created_by'] ?? [])->first()['name']
                ?? collect($detail['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name']
                ?? 'Bilinmiyor';
            $runtime = $detail['episode_run_time'][0] ?? $detail['last_episode_to_air']['runtime'] ?? null;
            $releaseDate = $detail['first_air_date'] ?? null;
        } else {
            $title = $detail['title'] ?? ($best['title'] ?? $item->original_query);
            $director = collect($detail['credits']['crew'] ?? [])->firstWhere('job', 'Director')['name'] ?? 'Bilinmiyor';
            $runtime = $detail['runtime'] ?? null;
            $releaseDate = $detail['release_date'] ?? null;
        }

        Movie::query()->create([
            'user_id' => $batch->user_id,
            'tmdb_id' => (string) $tmdbId,
            'media_type' => $mediaType,
            'title' => $title,
            'director' => $director,
            'genres' => $genres,
            'poster_path' => $detail['poster_path'] ?? null,
            'rating' => $detail['vote_average'] ?? null,
            'runtime' => $runtime,
            'overview' => empty($detail['overview']) ? null : $detail['overview'],
            'release_date' => $releaseDate,
            'is_watched' => $isWatched,
            'watched_at' => $isWatched ? now() : null,
        ]);

        $this->finalizeItem($batch->id, $item->id, 'saved', [
            'tmdb_id' => $tmdbId,
            'media_type' => $mediaType,
            'resolved_title' => $title,
            'was_corrected' => (bool) ($searchResult['corrected'] ?? false),
            'corrected_query' => $searchResult['corrected_query'] ?? null,
            'processed_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $item = ImportItem::query()->find($this->importItemId);
        if (! $item) {
            return;
        }

        $this->finalizeItem($item->import_batch_id, $item->id, 'error', [
            'error_message' => $exception->getMessage(),
            'processed_at' => now(),
        ]);
    }

    private function finalizeItem(int $batchId, int $itemId, string $status, array $itemData): void
    {
        DB::transaction(function () use ($batchId, $itemId, $status, $itemData) {
            $item = ImportItem::query()->lockForUpdate()->find($itemId);
            $batch = ImportBatch::query()->lockForUpdate()->find($batchId);
            if (! $item || ! $batch) {
                return;
            }

            $oldStatus = $item->status;
            $item->update(array_merge($itemData, ['status' => $status]));

            if ($oldStatus !== 'processing') {
                return;
            }

            $batch->processed_items++;
            if ($status === 'saved') {
                $batch->success_items++;
            } elseif ($status === 'duplicate') {
                $batch->duplicate_items++;
            } elseif ($status === 'not_found') {
                $batch->not_found_items++;
            } elseif ($status === 'skipped') {
                $batch->skipped_items++;
            } else {
                $batch->error_items++;
            }

            if ($batch->processed_items >= $batch->total_items) {
                $batch->status = 'finished';
                $batch->finished_at = now();
            }

            $batch->save();
        });
    }
}

