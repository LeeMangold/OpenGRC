<?php

namespace Tests\Feature;

use App\Enums\RecurrenceFrequency;
use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\Approval;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test checklist template can be created.
     */
    public function test_checklist_template_can_be_created(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Monthly Security Checklist',
            'description' => 'Monthly security verification tasks',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        $this->assertDatabaseHas('survey_templates', [
            'title' => 'Monthly Security Checklist',
            'type' => SurveyType::INTERNAL_CHECKLIST->value,
        ]);

        $this->assertTrue($template->isChecklist());
    }

    /**
     * Test checklist template with recurrence settings.
     */
    public function test_checklist_template_with_recurrence(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Weekly Review',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
            'default_assignee_id' => $assignee->id,
            'recurrence_frequency' => RecurrenceFrequency::WEEKLY,
            'recurrence_interval' => 1,
            'recurrence_day_of_week' => 1, // Monday
        ]);

        $this->assertEquals(RecurrenceFrequency::WEEKLY, $template->recurrence_frequency);
        $this->assertEquals(1, $template->recurrence_interval);
        $this->assertEquals(1, $template->recurrence_day_of_week);
        $this->assertEquals($assignee->id, $template->default_assignee_id);
    }

    /**
     * Test checklist can be created from template.
     */
    public function test_checklist_can_be_created_from_template(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Security Checklist',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        $checklist = Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'assigned_to_id' => $assignee->id,
            'created_by_id' => $user->id,
            'due_date' => now()->addWeek(),
        ]);

        $this->assertDatabaseHas('surveys', [
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST->value,
            'assigned_to_id' => $assignee->id,
        ]);

        $this->assertTrue($checklist->isChecklist());
        $this->assertEquals($template->id, $checklist->survey_template_id);
    }

    /**
     * Test checklist scopes filter correctly.
     */
    public function test_checklist_scope_filters_correctly(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Checklist Template',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        // Create a checklist
        Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        // Create a regular survey (vendor assessment)
        Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::VENDOR_ASSESSMENT,
            'status' => SurveyStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        $checklists = Survey::checklists()->get();
        $this->assertCount(1, $checklists);
        $this->assertEquals(SurveyType::INTERNAL_CHECKLIST, $checklists->first()->type);
    }

    /**
     * Test checklist approval workflow.
     */
    public function test_checklist_approval_workflow(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Approval Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        $checklist = Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::COMPLETED,
            'completed_at' => now(),
            'created_by_id' => $user->id,
            'approver_id' => $approver->id,
        ]);

        $this->assertFalse($checklist->isApproved());

        // Approve the checklist using the Approvable trait
        $approval = $checklist->approve($approver, 'John Doe', 'Approved after review');

        $checklist->refresh();
        $this->assertTrue($checklist->isApproved());
        $this->assertEquals($approver->id, $approval->approver_id);
        $this->assertEquals('John Doe', $approval->signature);
        $this->assertEquals('Approved after review', $approval->notes);
    }

    /**
     * Test template is locked when checklists exist.
     */
    public function test_template_is_locked_when_checklists_exist(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Lock Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        // Template should not be locked initially
        $this->assertFalse($template->isLocked());
        $this->assertFalse($template->hasChecklists());

        // Create a checklist from template
        Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        // Refresh and check lock status
        $template->refresh();
        $this->assertTrue($template->hasChecklists());
        $this->assertTrue($template->isLocked());
    }

    /**
     * Test calculate next due date for weekly recurrence.
     */
    public function test_calculate_next_due_date_weekly(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Weekly Due Date Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
            'recurrence_frequency' => RecurrenceFrequency::WEEKLY,
            'recurrence_interval' => 1,
        ]);

        $nextDue = $template->calculateNextDueDate();
        $this->assertNotNull($nextDue);
        $this->assertTrue($nextDue->isAfter(now()));
    }

    /**
     * Test calculate next due date for monthly recurrence.
     */
    public function test_calculate_next_due_date_monthly(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Monthly Due Date Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
            'recurrence_frequency' => RecurrenceFrequency::MONTHLY,
            'recurrence_interval' => 1,
            'recurrence_day_of_month' => 15,
        ]);

        $nextDue = $template->calculateNextDueDate();
        $this->assertNotNull($nextDue);
        $this->assertTrue($nextDue->isAfter(now()));
    }

    /**
     * Test default assignee relationship.
     */
    public function test_default_assignee_relationship(): void
    {
        $user = User::factory()->create();
        $assignee = User::factory()->create(['name' => 'Default Assignee']);

        $template = SurveyTemplate::create([
            'title' => 'Assignee Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
            'default_assignee_id' => $assignee->id,
        ]);

        $this->assertInstanceOf(User::class, $template->defaultAssignee);
        $this->assertEquals('Default Assignee', $template->defaultAssignee->name);
    }

    /**
     * Test approver relationship and approval workflow.
     */
    public function test_approver_relationship(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create(['name' => 'Checklist Approver']);

        $template = SurveyTemplate::create([
            'title' => 'Approver Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        $checklist = Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::COMPLETED,
            'completed_at' => now(),
            'created_by_id' => $user->id,
            'approver_id' => $approver->id,
        ]);

        // Test designated approver relationship
        $this->assertInstanceOf(User::class, $checklist->approver);
        $this->assertEquals('Checklist Approver', $checklist->approver->name);

        // Approve the checklist and verify the approval record
        $approval = $checklist->approve($approver, 'Checklist Approver');

        $this->assertInstanceOf(User::class, $approval->approver);
        $this->assertEquals('Checklist Approver', $approval->approver_name);
    }

    /**
     * Test checklist soft delete.
     */
    public function test_checklist_can_be_soft_deleted(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Delete Test',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        $checklist = Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        $checklistId = $checklist->id;
        $checklist->delete();

        $this->assertSoftDeleted('surveys', ['id' => $checklistId]);
        $this->assertNull(Survey::find($checklistId));
        $this->assertNotNull(Survey::withTrashed()->find($checklistId));
    }

    /**
     * Test display title attribute.
     */
    public function test_display_title_returns_template_title_when_no_override(): void
    {
        $user = User::factory()->create();

        $template = SurveyTemplate::create([
            'title' => 'Template Title',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyTemplateStatus::ACTIVE,
            'created_by_id' => $user->id,
        ]);

        $checklist = Survey::create([
            'survey_template_id' => $template->id,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        $this->assertEquals('Template Title', $checklist->display_title);

        // Test with title override
        $checklistWithTitle = Survey::create([
            'survey_template_id' => $template->id,
            'title' => 'Custom Title',
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'created_by_id' => $user->id,
        ]);

        $this->assertEquals('Custom Title', $checklistWithTitle->display_title);
    }
}
