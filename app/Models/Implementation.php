<?php

namespace App\Models;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Class Implementation
 *
 * @package App\Models
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
 * @mixin Eloquent
 */
class Implementation extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Indicates if the model should be indexed as you type.
     *
     * @var bool
     */
    public $asYouType = true;

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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['details', 'status', 'notes', 'effectiveness'];

    /**
     * The controls that belong to the implementation.
     *
     * @return BelongsToMany
     */
    public function controls(): BelongsToMany
    {
        return $this->belongsToMany(Control::class)
            ->withTimestamps();
    }

    /**
     * Get the name of the index associated with the model.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'implementations_index';
    }

    /**
     * Get the array representation of the model for search.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    /**
     * Get the audit items for the implementation.
     *
     * @return HasMany
     */
    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }

    /**
     * Get the completed audit items for the implementation.
     *
     * @return HasMany
     */
    public function completedAuditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class)->where('status', '=', 'Completed');
    }

    /**
     * Get the effectiveness of the implementation.
     *
     * @return Effectiveness
     */
    public function getEffectiveness(): Effectiveness
    {
        return $this->completedAuditItems->pluck('effectiveness')->last() ? $this->auditItems->pluck('effectiveness')->last() : Effectiveness::UNKNOWN;
    }

    /**
     * Get the date of the last effectiveness update.
     *
     * @return string
     */
    public function getEffectivenessDate(): string
    {
        return $this->completedAuditItems->pluck('effectiveness')->last() ? $this->auditItems->pluck('updated_at')->last() : "";
    }

    /**
     * Get all the audit items for the implementation.
     *
     * @return MorphMany
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(AuditItem::class, 'auditable');
    }

}
