<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ImportBatch;
use App\Models\ImportItem;
use App\Models\Movie;

$batch = ImportBatch::where('status', 'finished')->latest()->first();
$userId = $batch->user_id;

echo "=== SAVED Film - media_type Analizi ===\n\n";

$saved = $batch->items()->where('status', 'saved')->get();

foreach ($saved as $item) {
    echo "Import: \"{$item->original_query}\"\n";
    echo "  → Eklenen: {$item->resolved_title} (TMDB: {$item->tmdb_id}, type: {$item->media_type})\n";
    
    // Arşivde aynı isimle FARKLI tmdb_id veya media_type var mı?
    $similar = Movie::where('user_id', $userId)
        ->where('title', 'LIKE', '%' . explode(':', $item->original_query)[0] . '%')
        ->where(function($q) use ($item) {
            $q->where('tmdb_id', '!=', $item->tmdb_id)
              ->orWhere('media_type', '!=', $item->media_type);
        })
        ->get();
    
    if ($similar->count() > 0) {
        echo "  ⚠️  ARŞİVDE BENZER AMA FARKLI:\n";
        foreach ($similar as $m) {
            echo "     - {$m->title} (TMDB: {$m->tmdb_id}, type: {$m->media_type})\n";
        }
    }
    echo "\n";
}
