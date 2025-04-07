<?php

namespace App\Models;

use App\Enums\StandardStatus;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class Standard
 *
 * @property int $id
 * @property StandardStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Control[] $controls
 * @property-read int|null $controls_count
 *
 * @method static Builder|Standard newModelQuery()
 * @method static Builder|Standard newQuery()
 * @method static Builder|Standard onlyTrashed()
 * @method static Builder|Standard query()
 * @method static Builder|Standard whereCreatedAt($value)
 * @method static Builder|Standard whereDeletedAt($value)
 * @method static Builder|Standard whereId($value)
 * @method static Builder|Standard whereStatus($value)
 * @method static Builder|Standard whereUpdatedAt($value)
 * @method static Builder|Standard withTrashed()
 * @method static Builder|Standard withoutTrashed()
 *
 * @mixin Eloquent
 */
class Standard extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'integer',
        'status' => StandardStatus::class,
    ];

    /**
     * Get the controls for the standard.
     */
    public function controls(): HasMany
    {
        return $this->hasMany(Control::class);
    }

    /**
     * Get the audits for the standard.
     */
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class, 'sid');
    }

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'standards_index';
    }

    /**
     * Get the array representation of the model for search.
     */
    public function toSearchableArray(): array
    {
        return $this->toArray();
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }
}
