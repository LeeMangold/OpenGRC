<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Enums\RiskImpact;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Services\VendorRiskScoringService;
use Database\Seeders\VendorSurveyTemplatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorAssessmentScoringTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function makeTemplate(SurveyType $type, SurveyTemplateStatus $status = SurveyTemplateStatus::ACTIVE, string $title = 'Template'): SurveyTemplate
    {
        return SurveyTemplate::create([
            'title' => $title,
            'status' => $status,
            'type' => $type,
            'created_by_id' => $this->user->id,
        ]);
    }

    private function makeQuestion(SurveyTemplate $template, QuestionType $type, int $weight = 0, RiskImpact $impact = RiskImpact::NEUTRAL): SurveyQuestion
    {
        return SurveyQuestion::create([
            'survey_template_id' => $template->id,
            'question_text' => 'Question '.uniqid(),
            'question_type' => $type,
            'is_required' => true,
            'sort_order' => 1,
            'risk_weight' => $weight,
            'risk_impact' => $impact,
        ]);
    }

    private function makeSurvey(SurveyTemplate $template): Survey
    {
        return Survey::create([
            'survey_template_id' => $template->id,
            'created_by_id' => $this->user->id,
        ]);
    }

    public function test_vendor_assessment_scope_excludes_checklists_and_inactive_templates(): void
    {
        $vendor = $this->makeTemplate(SurveyType::VENDOR_ASSESSMENT, title: 'Vendor');
        $questionnaire = $this->makeTemplate(SurveyType::QUESTIONNAIRE, title: 'Questionnaire');
        $this->makeTemplate(SurveyType::INTERNAL_CHECKLIST, title: 'Checklist');
        $this->makeTemplate(SurveyType::VENDOR_ASSESSMENT, SurveyTemplateStatus::DRAFT, 'Draft');

        $ids = SurveyTemplate::forVendorAssessment()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$vendor->id, $questionnaire->id], $ids);
    }

    public function test_boolean_answers_are_interpreted_correctly(): void
    {
        $template = $this->makeTemplate(SurveyType::VENDOR_ASSESSMENT);
        $question = $this->makeQuestion($template, QuestionType::BOOLEAN);
        $survey = $this->makeSurvey($template);

        $cases = [
            ['yes', true],
            ['no', false],
            [true, true],
            [false, false],
            [['value' => 'no'], false],
            [null, null],
        ];

        foreach ($cases as [$stored, $expected]) {
            $answer = SurveyAnswer::updateOrCreate(
                ['survey_id' => $survey->id, 'survey_question_id' => $question->id],
                ['answer_value' => $stored],
            )->fresh();

            $this->assertSame($expected, $answer->booleanValue(), 'Stored value: '.json_encode($stored));
        }
    }

    public function test_survey_without_weighted_questions_is_not_scored(): void
    {
        $template = $this->makeTemplate(SurveyType::INTERNAL_CHECKLIST);
        $question = $this->makeQuestion($template, QuestionType::BOOLEAN);
        $survey = $this->makeSurvey($template);

        SurveyAnswer::create([
            'survey_id' => $survey->id,
            'survey_question_id' => $question->id,
            'answer_value' => 'no',
        ]);

        $score = (new VendorRiskScoringService)->calculateSurveyScore($survey);

        $this->assertNull($score);
        $this->assertNull($survey->fresh()->risk_score);
    }

    public function test_weighted_boolean_answers_are_scored(): void
    {
        $template = $this->makeTemplate(SurveyType::VENDOR_ASSESSMENT);
        $yes = $this->makeQuestion($template, QuestionType::BOOLEAN, 10, RiskImpact::POSITIVE);
        $no = $this->makeQuestion($template, QuestionType::BOOLEAN, 10, RiskImpact::POSITIVE);
        $survey = $this->makeSurvey($template);

        SurveyAnswer::create(['survey_id' => $survey->id, 'survey_question_id' => $yes->id, 'answer_value' => 'yes']);
        SurveyAnswer::create(['survey_id' => $survey->id, 'survey_question_id' => $no->id, 'answer_value' => 'no']);

        $score = (new VendorRiskScoringService)->calculateSurveyScore($survey);

        $this->assertSame(50, $score);
        $this->assertSame(50, $survey->fresh()->risk_score);
    }

    public function test_vendor_survey_seeder_only_adds_missing_templates(): void
    {
        (new VendorSurveyTemplatesSeeder)->run();

        $this->assertSame(2, SurveyTemplate::count());

        // Customisations survive a re-run, and deleted templates stay deleted.
        $survey = SurveyTemplate::where('title', 'Vendor Security Survey')->first();
        $survey->questions()->first()->update(['risk_weight' => 42]);
        SurveyTemplate::where('title', 'Vendor Security Survey (Internal)')->first()->delete();

        (new VendorSurveyTemplatesSeeder)->run();

        $this->assertSame(1, SurveyTemplate::count());
        $this->assertSame(2, SurveyTemplate::withTrashed()->count());
        $this->assertSame(42, $survey->questions()->first()->risk_weight);
    }
}
