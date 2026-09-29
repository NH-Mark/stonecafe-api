<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricalImportRow extends Model
{
     protected $fillable = [
        'historical_import_id',
        'external_order_no',
        'status',
        'source_total',
        'calculated_total',
        'data',
        'errors',
        'warnings',
        'order_id',
    ];

    protected $casts = [
        'source_total' => 'float',
        'calculated_total' => 'float',
        'data' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
    ];

    public function historicalImport(): BelongsTo
    {
        return $this->belongsTo(
            HistoricalImport::class
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }
}
