<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Enums\MitigationType;
use App\Mcp\Traits\HasMcpSupport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Risk extends Model
{
    use HasFactory, HasMcpSupport, HasTaxonomy, LogsActivity;

    protected $fillable = [
        'name',
        'likelihood',
        'impact',
    ];

    protected $casts = [
        'id' => 'integer',
        'action' => MitigationType::class,
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
            ->logOnly(['name', 'likelihood', 'impact', 'action'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
