<?php

namespace App\Models;

use App\Enums\StandardStatus;
use Eloquent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
 * @method static \Illuminate\Database\Eloquent\Builder|Standard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Standard newQuery()
 * @method static \Illuminate\Database\Query\Builder|Standard onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|Standard query()
 * @method static \Illuminate\Database\Eloquent\Builder|Standard whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Standard whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Standard whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Standard whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Standard whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Standard withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Standard withoutTrashed()
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
}
