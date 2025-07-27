<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DataRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'files' => 'array',
    ];

    protected $fillable = [
        'created_by_id',
        'assigned_to_id',
        'audit_item_id',
        'audit_id',
        'status',
        'details',
        'response',
        'files',
        'code', // Optional code for the data request, can be null, defaults to Request-{id}
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditItem(): BelongsTo
    {
        return $this->belongsTo(AuditItem::class);
    }

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(DataRequestResponse::class);
    }

    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(FileAttachment::class, DataRequestResponse::class);
    }
}
