<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FccDeadline extends Model
{
    protected $table = 'fcc_deadlines';

    protected $fillable = [
        'license_id', 'title', 'deadline_type', 'due_date', 'status', 'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(FccLicense::class, 'license_id');
    }

    public function getDaysUntilDueAttribute(): int
    {
        return now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }
}
