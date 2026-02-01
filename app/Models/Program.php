<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Mcp\Traits\HasMcpSupport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Program extends Model
{
    use HasFactory, HasMcpSupport, HasTaxonomy, LogsActivity;

    protected $fillable = [
        'name',
        'description',
        'program_manager_id',
        'last_audit_date',
        'scope_status',
    ];

    protected $casts = [
        'last_audit_date' => 'date',
    ];

    public function programManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'program_manager_id');
    }

    public function standards(): BelongsToMany
    {
        return $this->belongsToMany(Standard::class);
    }

    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class);
    }

    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    /**
     * Get all controls from standards and direct controls.
     *
     * @param  array<string>  $with  Relationships to eager load on controls
     */
    public function getAllControls(array $with = []): \Illuminate\Support\Collection
    {
        $standardControls = $this->standards()
            ->with(['controls' => fn ($query) => $query->with($with)])
            ->get()
            ->pluck('controls')
            ->flatten();

        $directControls = $this->controls()->with($with)->get();

        return $standardControls->concat($directControls)
            ->unique('id')
            ->values();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'description', 'program_manager_id', 'last_audit_date', 'scope_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
