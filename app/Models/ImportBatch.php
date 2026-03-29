<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'is_watched',
        'total_items',
        'processed_items',
        'success_items',
        'duplicate_items',
        'not_found_items',
        'error_items',
        'skipped_items',
        'started_at',
        'finished_at',
        'last_error',
    ];

    protected $casts = [
        'is_watched' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ImportItem::class);
    }
}

