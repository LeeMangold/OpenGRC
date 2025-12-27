<?php

namespace App\Models;

use App\Enums\TrustLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrustCenterDocument extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'trust_level',
        'requires_nda',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
        'valid_from',
        'valid_until',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'trust_level' => TrustLevel::class,
        'requires_nda' => 'boolean',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the user who uploaded this document.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the certifications associated with this document.
     */
    public function certifications(): BelongsToMany
    {
        return $this->belongsToMany(
            Certification::class,
            'certification_document'
        )->withTimestamps();
    }

    /**
     * Get the access requests that include this document.
     */
    public function accessRequests(): BelongsToMany
    {
        return $this->belongsToMany(
            TrustCenterAccessRequest::class,
            'access_request_document'
        )->withPivot('downloaded_at')->withTimestamps();
    }

    /**
     * Scope to get only public documents.
     */
    public function scopePublic($query)
    {
        return $query->where('trust_level', TrustLevel::PUBLIC);
    }

    /**
     * Scope to get only protected documents.
     */
    public function scopeProtected($query)
    {
        return $query->where('trust_level', TrustLevel::PROTECTED);
    }

    /**
     * Scope to get only active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this document is public.
     */
    public function isPublic(): bool
    {
        return $this->trust_level === TrustLevel::PUBLIC;
    }

    /**
     * Check if this document is protected.
     */
    public function isProtected(): bool
    {
        return $this->trust_level === TrustLevel::PROTECTED;
    }

    /**
     * Check if this document has expired.
     */
    public function isExpired(): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return $this->valid_until->isPast();
    }

    /**
     * Check if this document is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->valid_until) {
            return false;
        }

        return $this->valid_until->isBetween(now(), now()->addDays($days));
    }

    /**
     * Get the number of days until expiration.
     */
    public function daysUntilExpiration(): ?int
    {
        if (! $this->valid_until) {
            return null;
        }

        return (int) now()->diffInDays($this->valid_until, false);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'trust_level',
                'is_active',
                'valid_until',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
