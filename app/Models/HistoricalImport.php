<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalImport extends Model
{
      protected $fillable = [
        'source',
        'status',
        'orders_file_path',
        'items_file_path',
        'location_id',
        'total_orders',
        'valid_orders',
        'invalid_orders',
        'imported_orders',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(HistoricalImportRow::class);
    }
}
