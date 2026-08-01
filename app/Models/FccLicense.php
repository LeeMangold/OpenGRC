<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FccLicense extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fcc_licenses';

    protected $fillable = [
        'frn', 'call_sign', 'licensee', 'service', 'channel_or_frequency',
        'grant_date', 'expiration_date', 'last_renewal_date', 'status',
        'compliance_score', 'facility_id', 'license_class_data', 'public_notes',
    ];

    protected $casts = [
        'grant_date' => 'date',
        'expiration_date' => 'date',
        'last_renewal_date' => 'date',
        'compliance_score' => 'decimal:2',
        'license_class_data' => 'array',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(FccFacility::class, 'facility_id');
    }

    public function transmitters(): HasMany
    {
        return $this->hasMany(FccTransmitter::class, 'license_id');
    }

    public function ruleStatuses(): HasMany
    {
        return $this->hasMany(FccLicenseRuleStatus::class, 'license_id');
    }

    public function rules(): BelongsToMany
    {
        return $this->belongsToMany(FccRule::class, 'fcc_license_rule_status', 'license_id', 'fcc_rule_id')
            ->withPivot(['status', 'last_evaluated_at', 'evaluation_notes', 'evidence_path'])
            ->withTimestamps();
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(FccDeadline::class, 'license_id');
    }

    public function complianceEvents(): HasMany
    {
        return $this->hasMany(FccComplianceEvent::class, 'license_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'expiring_soon' => 'warning',
            'at_risk' => 'warning',
            'non_compliant' => 'danger',
            'silent', 'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
