<?php

namespace App\Models;

use App\Enums\RecurrenceFrequency;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SurveyTemplate extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'type',
        'created_by_id',
        'default_assignee_id',
        'recurrence_frequency',
        'recurrence_interval',
        'recurrence_day_of_week',
        'recurrence_day_of_month',
        'last_checklist_generated_at',
        'next_checklist_due_at',
    ];

    protected $casts = [
        'status' => SurveyTemplateStatus::class,
        'type' => SurveyType::class,
        'recurrence_frequency' => RecurrenceFrequency::class,
        'recurrence_interval' => 'integer',
        'recurrence_day_of_week' => 'integer',
        'recurrence_day_of_month' => 'integer',
        'last_checklist_generated_at' => 'datetime',
        'next_checklist_due_at' => 'datetime',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    /**
     * Scope a query to only include checklist templates.
     */
    public function scopeChecklists(Builder $query): Builder
    {
        return $query->where('type', SurveyType::INTERNAL_CHECKLIST);
    }

    /**
     * Check if this template is a checklist template.
     */
    public function isChecklist(): bool
    {
        return $this->type === SurveyType::INTERNAL_CHECKLIST;
    }

    /**
     * Check if this template has any checklists (surveys of type INTERNAL_CHECKLIST).
     */
    public function hasChecklists(): bool
    {
        return $this->surveys()
            ->where('type', SurveyType::INTERNAL_CHECKLIST)
            ->exists();
    }

    /**
     * Check if this template is locked (has checklists, preventing edits to questions).
     */
    public function isLocked(): bool
    {
        return $this->isChecklist() && $this->hasChecklists();
    }

    /**
     * Calculate the next due date based on recurrence settings.
     */
    public function calculateNextDueDate(?Carbon $fromDate = null): ?Carbon
    {
        if (! $this->recurrence_frequency) {
            return null;
        }

        $fromDate = $fromDate ?? now();
        $interval = $this->recurrence_interval ?? 1;

        $nextDate = match ($this->recurrence_frequency) {
            RecurrenceFrequency::DAILY => $fromDate->copy()->addDays($interval),
            RecurrenceFrequency::WEEKLY => $this->calculateWeeklyDate($fromDate, $interval),
            RecurrenceFrequency::MONTHLY => $this->calculateMonthlyDate($fromDate, $interval),
            RecurrenceFrequency::QUARTERLY => $fromDate->copy()->addMonths($interval * 3),
            RecurrenceFrequency::YEARLY => $fromDate->copy()->addYears($interval),
            default => null,
        };

        return $nextDate;
    }

    /**
     * Calculate the next weekly due date.
     */
    protected function calculateWeeklyDate(Carbon $fromDate, int $interval): Carbon
    {
        $nextDate = $fromDate->copy()->addWeeks($interval);

        if ($this->recurrence_day_of_week !== null) {
            $nextDate = $nextDate->startOfWeek()->addDays($this->recurrence_day_of_week);
            if ($nextDate->lte($fromDate)) {
                $nextDate = $nextDate->addWeeks($interval);
            }
        }

        return $nextDate;
    }

    /**
     * Calculate the next monthly due date.
     */
    protected function calculateMonthlyDate(Carbon $fromDate, int $interval): Carbon
    {
        $nextDate = $fromDate->copy()->addMonths($interval);

        if ($this->recurrence_day_of_month !== null) {
            $dayOfMonth = min($this->recurrence_day_of_month, $nextDate->daysInMonth);
            $nextDate = $nextDate->setDay($dayOfMonth);
            if ($nextDate->lte($fromDate)) {
                $nextDate = $nextDate->addMonths($interval);
                $dayOfMonth = min($this->recurrence_day_of_month, $nextDate->daysInMonth);
                $nextDate = $nextDate->setDay($dayOfMonth);
            }
        }

        return $nextDate;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'status', 'type', 'default_assignee_id', 'recurrence_frequency'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
