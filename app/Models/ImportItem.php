<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportItem extends Model
{
    protected $fillable = [
        'import_batch_id',
        'line_number',
        'original_query',
        'normalized_query',
        'tmdb_id',
        'media_type',
        'resolved_title',
        'status',
        'was_corrected',
        'corrected_query',
        'error_message',
        'processed_at',
    ];

    protected $casts = [
        'was_corrected' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}

