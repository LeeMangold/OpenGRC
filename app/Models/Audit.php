<?php

namespace App\Models;

use App\Enums\WorkflowStatus;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;

/**
 * Class Audit
 *
 * @package App\Models
 * @property int $id
 * @property string $title
 * @property string $description
 * @property WorkflowStatus $status
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|AuditItem[] $auditItems
 * @property-read int|null $auditItems_count
 * @property-read User $manager
 * @property-read Collection|DataRequest[] $dataRequest
 * @property-read int|null $dataRequest_count
 * @property-read Collection|FileAttachment[] $attachments
 * @property-read int|null $attachments_count
 * @method static \Illuminate\Database\Eloquent\Builder|Audit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Audit newQuery()
 * @method static Builder|Audit onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Audit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Audit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Audit whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Audit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Audit whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Audit whereUpdatedAt($value)
 * @method static Builder|Audit withTrashed()
 * @method static Builder|Audit withoutTrashed()
 * @mixin Eloquent
 */
class Audit extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['title', 'description', 'status', 'start_date', 'end_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'controls' => 'array',
        'status' => WorkflowStatus::class,
    ];

    /**
     * Get the audit items for the audit.
     *
     * @return HasMany
     */
    public function auditItems(): HasMany
    {
        return $this->hasMany(AuditItem::class);
    }

    /**
     * Get the manager that owns the audit.
     *
     * @return BelongsTo
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get the members that are part of the audit
     *
     * @return BelongsToMany
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the data requests for the audit.
     *
     * @return HasMany
     */
    public function dataRequest(): HasMany
    {
        return $this->hasMany(DataRequest::class);
    }

    /**
     * Get the file attachments for the audit through data requests and responses.
     *
     * @return HasMany
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(FileAttachment::class);
    }

}
