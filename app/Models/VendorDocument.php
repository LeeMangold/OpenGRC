<?php

namespace App\Models;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class VendorDocument extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'uploaded_by',
        'reviewed_by',
        'document_type',
        'name',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'issue_date',
        'expiration_date',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'document_type' => VendorDocumentType::class,
        'status' => VendorDocumentStatus::class,
        'issue_date' => 'date',
        'expiration_date' => 'date',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(VendorUser::class, 'uploaded_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isExpired(): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        return $this->expiration_date->isBetween(now(), now()->addDays($days));
    }

    public function daysUntilExpiration(): ?int
    {
        if (! $this->expiration_date) {
            return null;
        }

        return (int) now()->diffInDays($this->expiration_date, false);
    }

    public function scopePending($query)
    {
        return $query->where('status', VendorDocumentStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', VendorDocumentStatus::APPROVED);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', VendorDocumentStatus::APPROVED)
            ->whereNotNull('expiration_date')
            ->whereBetween('expiration_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'document_type',
                'name',
                'status',
                'expiration_date',
                'review_notes',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
