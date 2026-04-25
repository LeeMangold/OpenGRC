<?php

namespace App\Models;

use App\Enums\MitigationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Mitigation extends Model
{
    use HasFactory, LogsActivity;

    protected $casts = [
        'id' => 'integer',
        'strategy' => MitigationType::class,
        'date_implemented' => 'date',
    ];

    protected $fillable = [
        'description',
        'date_implemented',
        'strategy',
        'mitigatable_id',
        'mitigatable_type',
    ];

    public function mitigatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'description',
                'date_implemented',
                'strategy',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
