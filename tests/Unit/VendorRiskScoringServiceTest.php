<?php

namespace Tests\Unit;

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\VendorRiskRating;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Services\VendorRiskScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorRiskScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VendorRiskScoringService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VendorRiskScoringService;
    }

    public function test_calculate_survey_score_returns_zero_when_no_template()
    {
        $survey = Survey::factory()->create(['template_id' => null]);

        $score = $this->service->calculateSurveyScore($survey);

        $this->assertEquals(0, $score);
    }

    public function test_calculate_survey_score_returns_zero_when_no_weighted_questions()
    {
        $template = SurveyTemplate::factory()->create();
        $survey = Survey::factory()->create(['template_id' => $template->id]);

        // Create a question with zero weight
        SurveyQuestion::factory()->create([
            'template_id' => $template->id,
            'risk_weight' => 0,
        ]);

        $score = $this->service->calculateSurveyScore($survey);

        $this->assertEquals(0, $score);
    }

    public function test_get_answer_score_boolean_positive_impact()
    {
        $question = SurveyQuestion::factory()->create([
            'question_type' => QuestionType::BOOLEAN,
            'risk_impact' => RiskImpact::POSITIVE,
        ]);

        // Yes answer for positive impact = good (0 risk)
        $yesAnswer = SurveyAnswer::factory()->create(['answer_value' => true]);
        $score = $this->service->getAnswerScore($question, $yesAnswer);
        $this->assertEquals(0, $score);

        // No answer for positive impact = bad (100 risk)
        $noAnswer = SurveyAnswer::factory()->create(['answer_value' => false]);
        $score = $this->service->getAnswerScore($question, $noAnswer);
        $this->assertEquals(100, $score);
    }

    public function test_get_answer_score_boolean_negative_impact()
    {
        $question = SurveyQuestion::factory()->create([
            'question_type' => QuestionType::BOOLEAN,
            'risk_impact' => RiskImpact::NEGATIVE,
        ]);

        // Yes answer for negative impact = bad (100 risk)
        $yesAnswer = SurveyAnswer::factory()->create(['answer_value' => true]);
        $score = $this->service->getAnswerScore($question, $yesAnswer);
        $this->assertEquals(100, $score);

        // No answer for negative impact = good (0 risk)
        $noAnswer = SurveyAnswer::factory()->create(['answer_value' => false]);
        $score = $this->service->getAnswerScore($question, $noAnswer);
        $this->assertEquals(0, $score);
    }

    public function test_get_answer_score_neutral_impact_returns_zero()
    {
        $question = SurveyQuestion::factory()->create([
            'question_type' => QuestionType::BOOLEAN,
            'risk_impact' => RiskImpact::NEUTRAL,
        ]);

        $answer = SurveyAnswer::factory()->create(['answer_value' => true]);
        $score = $this->service->getAnswerScore($question, $answer);

        $this->assertEquals(0, $score);
    }

    public function test_recommend_risk_rating_thresholds()
    {
        // Test each threshold boundary
        $this->assertEquals(VendorRiskRating::VERY_LOW, $this->service->recommendRiskRating(15));
        $this->assertEquals(VendorRiskRating::LOW, $this->service->recommendRiskRating(35));
        $this->assertEquals(VendorRiskRating::MEDIUM, $this->service->recommendRiskRating(55));
        $this->assertEquals(VendorRiskRating::HIGH, $this->service->recommendRiskRating(75));
        $this->assertEquals(VendorRiskRating::CRITICAL, $this->service->recommendRiskRating(95));
    }
}
