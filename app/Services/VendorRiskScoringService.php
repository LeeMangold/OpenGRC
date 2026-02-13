<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\VendorRiskRating;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\Vendor;

class VendorRiskScoringService
{
    /**
     * Calculate the risk score for a survey based on answers and question weights.
     */
    public function calculateSurveyScore(Survey $survey): int
    {
        $template = $survey->template;

        if (! $template) {
            return 0;
        }

        $questions = $template->questions()
            ->where('risk_weight', '>', 0)
            ->get();

        if ($questions->isEmpty()) {
            return 0;
        }

        $totalWeight = 0;
        $weightedScoreSum = 0;

        foreach ($questions as $question) {
            $answer = $survey->answers()
                ->where('survey_question_id', $question->id)
                ->first();

            $answerScore = $this->getAnswerScore($question, $answer);

            // null means N/A - exclude this question from scoring
            if ($answerScore === null) {
                continue;
            }

            $weight = $question->risk_weight;
            $totalWeight += $weight;
            $weightedScoreSum += ($answerScore * $weight);
        }

        if ($totalWeight === 0) {
            return 0;
        }

        // Calculate weighted average (0-100)
        $score = (int) round($weightedScoreSum / $totalWeight);

        // Update the survey with the calculated score
        $survey->update([
            'risk_score' => $score,
            'risk_score_calculated_at' => now(),
        ]);

        return $score;
    }

    /**
     * Get the risk score for a specific answer based on question type and settings.
     * Returns null if the question should be excluded from scoring (N/A).
     */
    public function getAnswerScore(SurveyQuestion $question, ?SurveyAnswer $answer): ?int
    {
        // No answer = assume worst case for risk calculation
        if (! $answer || $answer->answer_value === null) {
            return $question->is_required ? 100 : 0;
        }

        $value = $answer->answer_value;
        $impact = $question->risk_impact ?? RiskImpact::NEUTRAL;

        // For TEXT/LONG_TEXT questions, always use manual score if available
        // (these require human judgment regardless of impact setting)
        if (in_array($question->question_type, [QuestionType::TEXT, QuestionType::LONG_TEXT])) {
            if ($answer && $answer->manual_score !== null) {
                // -1 means N/A - return null to exclude from scoring
                if ($answer->manual_score === -1) {
                    return null;
                }

                return $answer->manual_score;
            }

            return null; // No manual score yet - exclude from scoring
        }

        // For other question types, neutral impact means no risk contribution
        if ($impact === RiskImpact::NEUTRAL) {
            return 0;
        }

        return match ($question->question_type) {
            QuestionType::BOOLEAN => $this->scoreBooleanAnswer($value, $impact),
            QuestionType::SINGLE_CHOICE => $this->scoreChoiceAnswer($value, $question),
            QuestionType::MULTIPLE_CHOICE => $this->scoreMultipleChoiceAnswer($value, $question),
            QuestionType::FILE => $this->scoreFileAnswer($value, $question),
            default => 0,
        };
    }

    /**
     * Score a boolean (yes/no) answer.
     */
    protected function scoreBooleanAnswer(mixed $value, RiskImpact $impact): int
    {
        if (is_array($value)) {
            $value = $value['value'] ?? $value[0] ?? false;
        }
        $isYes = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        // POSITIVE impact: Yes = good (0 risk), No = bad (100 risk)
        // NEGATIVE impact: Yes = bad (100 risk), No = good (0 risk)
        if ($impact === RiskImpact::POSITIVE) {
            return $isYes ? 0 : 100;
        }

        return $isYes ? 100 : 0;
    }

    /**
     * Score a single choice answer using option_scores mapping.
     */
    protected function scoreChoiceAnswer(mixed $value, SurveyQuestion $question): int
    {
        $optionScores = $question->option_scores ?? [];

        if (empty($optionScores)) {
            return 0;
        }

        // Extract string value from array-cast answer_value
        if (is_array($value)) {
            $stringValue = isset($value['value']) ? (string) $value['value'] : (string) ($value[0] ?? '');
        } else {
            $stringValue = (string) $value;
        }

        return isset($optionScores[$stringValue])
            ? (int) $optionScores[$stringValue]
            : 50; // Default to middle score if option not mapped
    }

    /**
     * Score a multiple choice answer (average of selected options).
     */
    protected function scoreMultipleChoiceAnswer(mixed $value, SurveyQuestion $question): int
    {
        $optionScores = $question->option_scores ?? [];

        if (empty($optionScores)) {
            return 0;
        }

        $selectedOptions = is_array($value) ? $value : [$value];

        if (empty($selectedOptions)) {
            return $question->is_required ? 100 : 0;
        }

        $scores = [];
        foreach ($selectedOptions as $option) {
            $stringOption = is_string($option) ? $option : (string) $option;
            $scores[] = isset($optionScores[$stringOption])
                ? (int) $optionScores[$stringOption]
                : 50;
        }

        return (int) round(array_sum($scores) / count($scores));
    }

    /**
     * Score a file upload answer.
     */
    protected function scoreFileAnswer(mixed $value, SurveyQuestion $question): int
    {
        // If file is required and not uploaded, high risk
        // If file is uploaded, no risk
        $hasFile = ! empty($value);

        if ($question->is_required && ! $hasFile) {
            return 100;
        }

        return $hasFile ? 0 : 50;
    }

    /**
     * Calculate the overall risk score for a vendor based on their surveys.
     */
    public function calculateVendorScore(Vendor $vendor): int
    {
        // Get the most recent completed survey with a risk score
        $latestSurvey = $vendor->surveys()
            ->whereNotNull('risk_score')
            ->orderBy('risk_score_calculated_at', 'desc')
            ->first();

        if (! $latestSurvey) {
            return 0;
        }

        // For now, vendor score = latest survey score
        // Future: could weight multiple surveys, include document compliance, etc.
        $score = $latestSurvey->risk_score;

        // Determine the risk rating based on the score
        $riskRating = $this->recommendRiskRating($score);

        $vendor->update([
            'risk_score' => $score,
            'risk_rating' => $riskRating,
            'risk_score_calculated_at' => now(),
        ]);

        return $score;
    }

    /**
     * Get a detailed breakdown of the survey score by question.
     */
    public function getScoreBreakdown(Survey $survey): array
    {
        $breakdown = [];
        $template = $survey->template;

        if (! $template) {
            return $breakdown;
        }

        $questions = $template->questions()
            ->where('risk_weight', '>', 0)
            ->get();

        foreach ($questions as $question) {
            $answer = $survey->answers()
                ->where('survey_question_id', $question->id)
                ->first();

            $score = $this->getAnswerScore($question, $answer);
            $isNA = $score === null;

            $breakdown[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'weight' => $question->risk_weight,
                'impact' => $question->risk_impact?->value,
                'answer_value' => $answer?->answer_value,
                'score' => $score,
                'is_na' => $isNA,
                'weighted_score' => $isNA ? 0 : (($score * $question->risk_weight) / 100),
            ];
        }

        return $breakdown;
    }

    /**
     * Recommend a risk rating based on the calculated score.
     */
    public function recommendRiskRating(int $score): VendorRiskRating
    {
        $thresholds = [
            'very_low' => (int) setting('vendor_portal.risk_threshold_very_low', 20),
            'low' => (int) setting('vendor_portal.risk_threshold_low', 40),
            'medium' => (int) setting('vendor_portal.risk_threshold_medium', 60),
            'high' => (int) setting('vendor_portal.risk_threshold_high', 80),
        ];

        if ($score <= $thresholds['very_low']) {
            return VendorRiskRating::VERY_LOW;
        }

        if ($score <= $thresholds['low']) {
            return VendorRiskRating::LOW;
        }

        if ($score <= $thresholds['medium']) {
            return VendorRiskRating::MEDIUM;
        }

        if ($score <= $thresholds['high']) {
            return VendorRiskRating::HIGH;
        }

        return VendorRiskRating::CRITICAL;
    }
}
