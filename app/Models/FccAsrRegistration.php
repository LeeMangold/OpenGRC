<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FccAsrRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fcc_asr_registrations';

    protected $fillable = [
        'asr_number', 'owner', 'structure_type', 'overall_height_meters',
        'latitude', 'longitude', 'faa_study_number', 'lighting_type',
        'painting_required', 'last_inspection_date', 'next_inspection_due',
    ];

    protected $casts = [
        'overall_height_meters' => 'decimal:2',
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'last_inspection_date' => 'date',
        'next_inspection_due' => 'date',
    ];

    public function inspections(): HasMany
    {
        return $this->hasMany(FccTowerLightingInspection::class, 'asr_registration_id');
    }

    public function outages(): HasMany
    {
        return $this->hasMany(FccTowerLightingOutage::class, 'asr_registration_id');
    }
}
