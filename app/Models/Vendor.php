<?php

namespace App\Models;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'url',
        'vendor_manager_id',
        'status',
        'risk_rating',
        'notes',
    ];

    protected $casts = [
        'status' => VendorStatus::class,
        'risk_rating' => VendorRiskRating::class,
    ];

    public function vendorManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_manager_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
} 