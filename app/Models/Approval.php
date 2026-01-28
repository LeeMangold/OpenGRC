<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'approver_id',
        'approver_name',
        'approver_email',
        'signature',
        'notes',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the approvable model (polymorphic).
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who approved (may be null if user was deleted).
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /**
     * Get the display name of the approver.
     * Uses the stored static name, falling back to the user relationship if available.
     */
    public function getApproverDisplayNameAttribute(): string
    {
        return $this->approver_name;
    }
}
