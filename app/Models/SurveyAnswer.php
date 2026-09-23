<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class SurveyAnswer extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'survey_id',
        'survey_question_id',
        'answer_value',
        'comment',
        'manual_score',
        'scored_by',
        'scored_at',
    ];

    protected $casts = [
        'answer_value' => 'array',
        'manual_score' => 'integer',
        'scored_at' => 'datetime',
    ];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class, 'survey_question_id');
    }

    public function scoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scored_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SurveyAttachment::class);
    }

    public function getDisplayValueAttribute(): string
    {
        if ($this->answer_value === null) {
            return '-';
        }

        $value = $this->answer_value;

        if (is_array($value)) {
            if (isset($value['value'])) {
                return (string) $value['value'];
            }

            return implode(', ', array_filter($value, fn ($v) => ! is_array($v)));
        }

        return (string) $value;
    }

    /**
     * Interpret the answer as a yes/no value.
     *
     * Boolean answers are stored as the strings 'yes'/'no', but older data may
     * hold real booleans or a ['value' => ...] wrapper. Returns null when the
     * answer is empty or not recognisable as yes/no.
     */
    public function booleanValue(): ?bool
    {
        $value = $this->answer_value;

        if (is_array($value)) {
            $value = $value['value'] ?? $value[0] ?? null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['answer_value', 'comment'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
