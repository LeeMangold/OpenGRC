<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccComplianceEvent extends Model
{
    protected $table = 'fcc_compliance_events';

    protected $fillable = [
        'license_id', 'event_type', 'summary', 'payload', 'actor', 'occurred_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }
}
