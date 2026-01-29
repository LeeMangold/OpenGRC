<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Enums\MitigationType;
use App\Enums\RiskStatus;
use App\Mcp\Traits\HasMcpSupport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Risk extends Model
{
    use HasFactory, HasMcpSupport, HasTaxonomy, LogsActivity;

    protected $casts = [
        'id' => 'integer',
        'action' => MitigationType::class,
        'status' => RiskStatus::class,
        'is_active' => 'boolean',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'inherent_likelihood',
        'inherent_impact',
        'inherent_risk',
        'residual_likelihood',
        'residual_impact',
        'residual_risk',
        'is_active',
    ];

    public function implementations(): BelongsToMany
    {
        return $this->BelongsToMany(Implementation::class);
    }

    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'policy_risk')
            ->withTimestamps();
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    public function mitigations(): MorphMany
    {
        return $this->morphMany(Mitigation::class, 'mitigatable');
    }

    public function latestMitigation(): ?Mitigation
    {
        return $this->mitigations()->latest('date_implemented')->first();
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'risks_index';
    }

    /**
     * Get the array representation of the model for search.
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    public static function next()
    {
        return static::max('id') + 1;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'code',
                'name',
                'description',
                'inherent_likelihood',
                'inherent_impact',
                'inherent_risk',
                'residual_likelihood',
                'residual_impact',
                'residual_risk',
                'status',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
