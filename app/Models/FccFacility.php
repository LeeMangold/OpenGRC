<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FccFacility extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fcc_facilities';

    protected $fillable = [
        'facility_id', 'name', 'community_of_license', 'state',
        'latitude', 'longitude', 'antenna_haat_meters', 'antenna_amsl_meters',
        'asr_number', 'owner', 'contact_engineer', 'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:6',
        'longitude' => 'decimal:6',
        'antenna_haat_meters' => 'decimal:2',
        'antenna_amsl_meters' => 'decimal:2',
    ];

    public function licenses(): HasMany
    {
        return $this->hasMany(FccLicense::class, 'facility_id');
    }
}
