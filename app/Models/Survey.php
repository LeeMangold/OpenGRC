<?php

namespace App\Models;

use App\Enums\SurveyStatus;
use App\Enums\SurveyType;
use App\Models\Concerns\Approvable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Survey extends Model
{
    use Approvable, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'survey_template_id',
        'title',
        'description',
        'status',
        'type',
        'respondent_email',
        'respondent_name',
        'assigned_to_id',
        'approver_id',
        'vendor_id',
        'due_date',
        'expiration_date',
        'access_token',
        'completed_at',
        'created_by_id',
        'risk_score',
        'risk_score_calculated_at',
    ];

    protected $casts = [
        'status' => SurveyStatus::class,
        'type' => SurveyType::class,
        'due_date' => 'date',
        'expiration_date' => 'date',
        'completed_at' => 'datetime',
        'risk_score' => 'integer',
        'risk_score_calculated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Survey $survey) {
            if (empty($survey->access_token)) {
                $survey->access_token = Str::random(64);
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Scope a query to only include checklists.
     */
    public function scopeChecklists(Builder $query): Builder
    {
        return $query->where('type', SurveyType::INTERNAL_CHECKLIST);
    }

    /**
     * Check if this survey is a checklist.
     */
    public function isChecklist(): bool
    {
        return $this->type === SurveyType::INTERNAL_CHECKLIST;
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?? $this->template?->title ?? 'Untitled Survey';
    }

    public function getRespondentDisplayAttribute(): string
    {
        if ($this->respondent_name && $this->respondent_email) {
            return "{$this->respondent_name} ({$this->respondent_email})";
        }

        return $this->respondent_name ?? $this->respondent_email ?? $this->assignedTo?->name ?? '-';
    }

    public function getProgressAttribute(): int
    {
        // Use eager-loaded counts if available, otherwise fall back to queries
        $totalQuestions = $this->template?->questions_count
            ?? $this->template?->questions()->count()
            ?? 0;

        if ($totalQuestions === 0) {
            return 0;
        }

        $answeredQuestions = $this->answered_questions_count
            ?? $this->answers()->whereNotNull('answer_value')->count();

        return (int) round(($answeredQuestions / $totalQuestions) * 100);
    }

    public function getRequiredProgressAttribute(): int
    {
        $requiredQuestions = $this->template?->questions()->where('is_required', true)->count() ?? 0;

        if ($requiredQuestions === 0) {
            return 100;
        }

        $requiredQuestionIds = $this->template->questions()
            ->where('is_required', true)
            ->pluck('id');

        $answeredRequiredQuestions = $this->answers()
            ->whereIn('survey_question_id', $requiredQuestionIds)
            ->whereNotNull('answer_value')
            ->count();

        return (int) round(($answeredRequiredQuestions / $requiredQuestions) * 100);
    }

    public function isExpired(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== SurveyStatus::COMPLETED;
    }

    public function isLinkExpired(): bool
    {
        return $this->expiration_date && $this->expiration_date->endOfDay()->isPast();
    }

    public function isInternal(): bool
    {
        return $this->assigned_to_id !== null && empty($this->respondent_email);
    }

    /**
     * Get a magic link URL for vendors to respond to this survey.
     * This is a signed URL that auto-logs in the vendor and redirects to the survey.
     *
     * @param  int|null  $expiryHours  Hours until link expires (default from settings)
     */
    public function getPublicUrl(?int $expiryHours = null): string
    {
        $expiryHours = $expiryHours ?? (int) setting('vendor_portal.magic_link_expiry_hours', 48);

        return URL::temporarySignedRoute(
            'vendor.survey.magic-link',
            now()->addHours($expiryHours),
            ['survey' => $this->id]
        );
    }

    /**
     * Get the direct Vendor panel URL (requires vendor authentication).
     */
    public function getVendorPanelUrl(): string
    {
        return url('/vendor/surveys/'.$this->id.'/respond');
    }

    /**
     * Get the legacy public URL using access token (for backwards compatibility).
     */
    public function getLegacyPublicUrl(): string
    {
        return url('/survey/'.$this->access_token);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'type', 'respondent_email', 'respondent_name', 'assigned_to_id', 'approver_id', 'due_date', 'expiration_date', 'completed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isVendorAssessment(): bool
    {
        return $this->type === SurveyType::VENDOR_ASSESSMENT || $this->type === null;
    }
}
