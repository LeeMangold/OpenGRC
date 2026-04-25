<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class SurveyAttachment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'survey_answer_id',
        'file_name',
        'file_path',
        'file_size',
        'uploaded_by',
    ];

    public function answer(): BelongsTo
    {
        return $this->belongsTo(SurveyAnswer::class, 'survey_answer_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the storage disk for this attachment.
     */
    public function getStorageDisk(): string
    {
        return setting('storage.driver', 'private');
    }

    /**
     * Check if the file exists in storage.
     */
    public function fileExists(): bool
    {
        return Storage::disk($this->getStorageDisk())->exists($this->file_path);
    }

    /**
     * Get the file contents.
     */
    public function getFileContents(): ?string
    {
        if ($this->fileExists()) {
            return Storage::disk($this->getStorageDisk())->get($this->file_path);
        }

        return null;
    }

    /**
     * Get a formatted file size string.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['file_name', 'file_size', 'uploaded_by', 'survey_answer_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
