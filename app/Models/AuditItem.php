<?php

namespace App\Models;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Enums\StandardStatus;
use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['audit_id', 'user_id', 'control_id', 'auditor_notes', 'status', 'effectiveness', 'applicability'];

    protected $casts = [
        'id' => 'integer',
        'applicability' => Applicability::class,
        'status' => WorkflowStatus::class,
        'effectiveness' => Effectiveness::class,
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function control(): BelongsTo
    {
        return $this->belongsTo(Control::class, 'control_id');
    }

    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class, 'implementation_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function dataRequests(): HasMany
    {
        return $this->hasMany(DataRequest::class);
    }

}
