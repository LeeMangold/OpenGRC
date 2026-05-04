<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccStationLogEntry extends Model
{
    protected $table = 'fcc_station_log_entries';

    protected $fillable = [
        'license_id', 'logged_at', 'entry_type', 'summary', 'readings', 'logged_by',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'readings' => 'array',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
