<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccEasTest extends Model
{
    protected $table = 'fcc_eas_tests';

    protected $fillable = [
        'license_id', 'test_type', 'direction', 'test_datetime',
        'originator_code', 'event_code', 'location_codes',
        'audio_intelligible', 'visual_message_present',
        'comments', 'filed_in_etrs', 'etrs_filed_date', 'logged_by',
    ];

    protected $casts = [
        'test_datetime' => 'datetime',
        'audio_intelligible' => 'boolean',
        'visual_message_present' => 'boolean',
        'filed_in_etrs' => 'boolean',
        'etrs_filed_date' => 'date',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
