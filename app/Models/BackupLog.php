<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $fillable = [
        'backup_name',
        'backup_type',
        'status',
        'storage_driver',
        'file_path',
        'file_name',
        'file_size',
        'checksum',
        'backup_config',
        'included_tables',
        'excluded_tables',
        'started_at',
        'completed_at',
        'duration',
        'error_message',
        'notes',
        'is_encrypted',
        'is_compressed',
        'verified',
        'verified_at',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'backup_config' => 'array',
        'included_tables' => 'array',
        'excluded_tables' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_encrypted' => 'boolean',
        'is_compressed' => 'boolean',
        'verified' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())->orWhereNull('expires_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'running' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'orange',
            default => 'gray'
        };
    }

    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $data = []): void
    {
        $this->update(array_merge([
            'status' => 'completed',
            'completed_at' => now(),
            'duration' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
        ], $data));
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration' => $this->started_at ? now()->diffInSeconds($this->started_at) : null,
            'error_message' => $error,
        ]);
    }
} 