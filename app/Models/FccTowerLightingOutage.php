<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccTowerLightingOutage extends Model
{
    protected $table = 'fcc_tower_lighting_outages';

    protected $fillable = [
        'asr_registration_id', 'outage_observed_at', 'faa_notified_at',
        'notam_number', 'repaired_at', 'faa_cancellation_at',
        'failure_type', 'cause', 'actions_taken',
    ];

    protected $casts = [
        'outage_observed_at' => 'datetime',
        'faa_notified_at' => 'datetime',
        'repaired_at' => 'datetime',
        'faa_cancellation_at' => 'datetime',
    ];

    public function asr(): BelongsTo
    {
        return $this->belongsTo(FccAsrRegistration::class, 'asr_registration_id');
    }
}
