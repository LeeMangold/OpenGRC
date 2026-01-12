<?php

namespace App\Models;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Mcp\Traits\HasMcpSupport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model
{
    use HasFactory, HasMcpSupport, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'url',
        'logo',
        'vendor_manager_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'address',
        'status',
        'risk_rating',
        'risk_score',
        'risk_score_calculated_at',
        'notes',
    ];

    protected $casts = [
        'status' => VendorStatus::class,
        'risk_rating' => VendorRiskRating::class,
        'logo' => 'array',
        'risk_score' => 'integer',
        'risk_score_calculated_at' => 'datetime',
    ];

    public function vendorManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_manager_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function vendorUsers(): HasMany
    {
        return $this->hasMany(VendorUser::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'risk_rating', 'vendor_manager_id', 'contact_name', 'contact_email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
