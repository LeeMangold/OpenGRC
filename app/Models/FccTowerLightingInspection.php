<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccTowerLightingInspection extends Model
{
    protected $table = 'fcc_tower_lighting_inspections';

    protected $fillable = [
        'asr_registration_id', 'facility_id', 'inspection_date',
        'inspector_name', 'result', 'automatic_monitor_observed',
        'manual_observation_performed', 'findings', 'corrective_action',
        'next_inspection_due',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'next_inspection_due' => 'date',
        'automatic_monitor_observed' => 'boolean',
        'manual_observation_performed' => 'boolean',
    ];

    public function asr(): BelongsTo
    {
        return $this->belongsTo(FccAsrRegistration::class, 'asr_registration_id');
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(FccFacility::class, 'facility_id');
    }
}
