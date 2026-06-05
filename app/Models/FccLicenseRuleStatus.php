<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccLicenseRuleStatus extends Model
{
    protected $table = 'fcc_license_rule_status';

    protected $fillable = [
        'license_id', 'fcc_rule_id', 'status',
        'last_evaluated_at', 'evaluation_notes', 'evidence_path',
    ];

    protected $casts = [
        'last_evaluated_at' => 'date',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FccRule::class, 'fcc_rule_id');
    }
}
