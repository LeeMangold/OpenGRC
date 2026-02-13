<?php

namespace App\Models;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Mcp\Traits\HasMcpSupport;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Class Implementation
 *
 * @property int $id
 * @property ImplementationStatus $status
 * @property Effectiveness $effectiveness
 * @property string $details
 * @property string $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Control[] $controls
 * @property-read int|null $controls_count
 * @property-read Collection|Audit[] $audits
 * @property-read int|null $audits_count
 * @property-read Collection|AuditItem[] $auditItems
 * @property-read int|null $auditItems_count
 * @property-read Collection|AuditItem[] $completedAuditItems
 * @property-read int|null $completedAuditItems_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation newQuery()
 * @method static Builder|Implementation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation query()
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Implementation whereUpdatedAt($value)
 * @method static Builder|Implementation withTrashed()
 * @method static Builder|Implementation withoutTrashed()
 *
 * @mixin Eloquent
 */
class Implementation extends Model
{
    use HasFactory, HasMcpSupport, HasTaxonomy, LogsActivity, SoftDeletes;

    /**
     * Indicates if the model should be indexed as you type.
     */
    public bool $asYouType = true;

    protected $fillable = ['code', 'title', 'details', 'status', 'notes', 'effectiveness', 'test_procedure', 'implementation_owner_id'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'status' => ImplementationStatus::class,
        'effectiveness' => Effectiveness::class,
    ];

    /**
     * The controls that belong to the implementation.
     */
    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class)
            ->withTimestamps();
    }

    /**
     * The policies that belong to the implementation.
     */
    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(Policy::class, 'implementation_policy')
            ->withTimestamps();
    }

    /**
     * The risks that belong to the implementation.
     */
    public function risks(): BelongsToMany
    {
        return $this->belongsToMany(Risk::class);
    }

    /**
     * The assets that belong to the implementation.
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class)
            ->withTimestamps();
    }

    /**
     * The applications that belong to the implementation.
     */
    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class)
            ->withTimestamps();
    }

    /**
     * The vendors that belong to the implementation.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class)
            ->withTimestamps();
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'implementations_index';
    }

    /**
     * Get the array representation of the model for search.
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    /**
     * Get the audit items for the implementation.
     */
    public function auditItems(): MorphMany
    {
        return $this->morphMany(AuditItem::class, 'auditable')
            ->where('auditable_type', '=', Implementation::class);
    }

    /**
     * Get the completed audit items for the implementation.
     */
    public function completedAuditItems(): MorphMany
    {
        return $this->morphMany(AuditItem::class, 'auditable')
            ->where('status', '=', 'Completed')
            ->where('auditable_type', '=', Implementation::class);
    }

    /**
     * Eager-loadable relationship for the latest completed audit item.
     */
    public function latestCompletedAudit(): MorphOne
    {
        return $this->morphOne(AuditItem::class, 'auditable')
            ->where('status', '=', 'Completed')
            ->latestOfMany('created_at');
    }

    /**
     * Get the effectiveness of the implementation.
     */
    public function getEffectiveness(): Effectiveness
    {
        // Use eager-loaded relationship if available
        if ($this->relationLoaded('latestCompletedAudit')) {
            return $this->latestCompletedAudit?->effectiveness ?? Effectiveness::UNKNOWN;
        }

        // Fallback to querying only the latest completed audit item
        return $this->latestCompletedAudit()->first()?->effectiveness ?? Effectiveness::UNKNOWN;
    }

    /**
     * Get the date of the last effectiveness update.
     */
    public function getEffectivenessDate(): string
    {
        // Use eager-loaded relationship if available
        if ($this->relationLoaded('latestCompletedAudit')) {
            return $this->latestCompletedAudit?->updated_at?->format('M d, Y') ?? '';
        }

        // Fallback to querying only the latest completed audit item
        return $this->latestCompletedAudit()->first()?->updated_at?->format('M d, Y') ?? '';
    }

    /**
     * Get the owner of the implementation.
     */
    public function implementationOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'implementation_owner_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'details', 'status', 'effectiveness', 'notes', 'test_procedure'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
