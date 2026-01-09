<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Mcp\Traits\HasMcpSupport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Application extends Model
{
    use HasFactory, HasMcpSupport, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'owner_id',
        'type',
        'description',
        'status',
        'url',
        'notes',
        'vendor_id',
    ];

    protected $casts = [
        'type' => ApplicationType::class,
        'status' => ApplicationStatus::class,
        'logo' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function implementations(): BelongsToMany
    {
        return $this->belongsToMany(Implementation::class)
            ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'status', 'owner_id', 'vendor_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
