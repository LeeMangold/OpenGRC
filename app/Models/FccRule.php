<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FccRule extends Model
{
    use HasFactory;

    protected $table = 'fcc_rules';

    protected $fillable = [
        'rule_number', 'part', 'title', 'description',
        'category', 'severity', 'quarterly_filing_required',
    ];

    protected $casts = [
        'quarterly_filing_required' => 'boolean',
    ];

    public function licenses(): BelongsToMany
    {
        return $this->belongsToMany(FccLicense::class, 'fcc_license_rule_status', 'fcc_rule_id', 'license_id')
            ->withPivot(['status', 'last_evaluated_at', 'evaluation_notes', 'evidence_path'])
            ->withTimestamps();
    }

    public function statuses(): HasMany
    {
        return $this->hasMany(FccLicenseRuleStatus::class, 'fcc_rule_id');
    }
}
