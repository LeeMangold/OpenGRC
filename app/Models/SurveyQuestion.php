<?php

namespace App\Models;

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class SurveyQuestion extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'survey_template_id',
        'question_text',
        'question_type',
        'options',
        'is_required',
        'sort_order',
        'help_text',
        'allow_comments',
        'risk_weight',
        'risk_impact',
        'option_scores',
    ];

    protected $casts = [
        'question_type' => QuestionType::class,
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'allow_comments' => 'boolean',
        'risk_weight' => 'integer',
        'risk_impact' => RiskImpact::class,
        'option_scores' => 'array',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SurveyTemplate::class, 'survey_template_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['question_text', 'question_type', 'options', 'is_required', 'sort_order', 'allow_comments'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
