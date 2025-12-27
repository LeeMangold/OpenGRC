<?php

namespace App\Models;

use App\Enums\AccessRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TrustCenterAccessRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'requester_name',
        'requester_email',
        'requester_company',
        'reason',
        'nda_agreed',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'access_token',
        'access_expires_at',
        'last_accessed_at',
        'access_count',
    ];

    protected $casts = [
        'status' => AccessRequestStatus::class,
        'nda_agreed' => 'boolean',
        'reviewed_at' => 'datetime',
        'access_expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count' => 'integer',
    ];

    /**
     * Get the user who reviewed this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the documents associated with this access request.
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(
            TrustCenterDocument::class,
            'access_request_document'
        )->withPivot('downloaded_at')->withTimestamps();
    }

    /**
     * Scope to get only pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', AccessRequestStatus::PENDING);
    }

    /**
     * Scope to get only approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', AccessRequestStatus::APPROVED);
    }

    /**
     * Scope to get only rejected requests.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', AccessRequestStatus::REJECTED);
    }

    /**
     * Check if the request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === AccessRequestStatus::PENDING;
    }

    /**
     * Check if the request is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === AccessRequestStatus::APPROVED;
    }

    /**
     * Check if the request is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === AccessRequestStatus::REJECTED;
    }

    /**
     * Check if the access is still valid.
     */
    public function isAccessValid(): bool
    {
        if (! $this->isApproved()) {
            return false;
        }

        if (! $this->access_expires_at) {
            return false;
        }

        return $this->access_expires_at->isFuture();
    }

    /**
     * Approve the access request.
     */
    public function approve(User $reviewer, ?string $notes = null): void
    {
        $expiryHours = (int) setting('trust_center.magic_link_expiry_hours', 24);

        $this->update([
            'status' => AccessRequestStatus::APPROVED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'access_token' => $this->generateAccessToken(),
            'access_expires_at' => now()->addHours($expiryHours),
        ]);
    }

    /**
     * Reject the access request.
     */
    public function reject(User $reviewer, ?string $notes = null): void
    {
        $this->update([
            'status' => AccessRequestStatus::REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }

    /**
     * Generate a unique access token.
     */
    public function generateAccessToken(): string
    {
        return Str::random(64);
    }

    /**
     * Record an access to the protected documents.
     */
    public function recordAccess(): void
    {
        $this->update([
            'last_accessed_at' => now(),
            'access_count' => $this->access_count + 1,
        ]);
    }

    /**
     * Get the signed access URL.
     */
    public function getAccessUrl(): string
    {
        return URL::temporarySignedRoute(
            'trust-center.protected-access',
            $this->access_expires_at,
            ['accessRequest' => $this->id]
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'requester_name',
                'requester_email',
                'requester_company',
                'status',
                'reviewed_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
