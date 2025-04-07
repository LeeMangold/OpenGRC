<?php

namespace App\Models;

use App\Enums\MitigationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Risk extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'action' => MitigationType::class,
    ];

    protected $fillable = [
        'name',
        'likelihood',
        'impact',
    ];

    public function implementations(): BelongsToMany
    {
        return $this->BelongsToMany(Implementation::class);
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
}
