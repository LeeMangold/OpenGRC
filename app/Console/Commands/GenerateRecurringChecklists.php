<?php

namespace App\Console\Commands;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use Illuminate\Console\Command;

class GenerateRecurringChecklists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'checklists:generate-recurring
                            {--dry-run : Show what would be generated without actually creating checklists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate checklists from recurring checklist templates that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('DRY RUN - No checklists will be created');
            $this->newLine();
        }

        // Find active checklist templates with recurrence that are due
        $templates = SurveyTemplate::query()
            ->where('type', SurveyType::INTERNAL_CHECKLIST)
            ->where('status', SurveyTemplateStatus::ACTIVE)
            ->whereNotNull('recurrence_frequency')
            ->where(function ($query) {
                $query->whereNull('next_checklist_due_at')
                    ->orWhere('next_checklist_due_at', '<=', now());
            })
            ->get();

        if ($templates->isEmpty()) {
            $this->info('No recurring checklist templates are due for generation.');

            return Command::SUCCESS;
        }

        $this->info("Found {$templates->count()} template(s) due for checklist generation:");
        $this->newLine();

        $generatedCount = 0;
        $errorCount = 0;

        foreach ($templates as $template) {
            $this->line("  Processing: {$template->title}");

            try {
                if ($isDryRun) {
                    $dueDate = $template->calculateNextDueDate();
                    $this->line("    Would create checklist with due date: {$dueDate?->format('Y-m-d')}");
                    $this->line('    Assignee: '.($template->defaultAssignee?->name ?? 'None'));
                    $generatedCount++;
                } else {
                    $checklist = $this->createChecklistFromTemplate($template);
                    $this->info("    Created checklist: {$checklist->display_title}");
                    $generatedCount++;
                }
            } catch (\Exception $e) {
                $this->error("    Error: {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("Summary: {$generatedCount} checklist(s) ".($isDryRun ? 'would be ' : '').'generated, '.$errorCount.' error(s)');

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Create a checklist from a recurring template.
     */
    protected function createChecklistFromTemplate(SurveyTemplate $template): Survey
    {
        $dueDate = $template->calculateNextDueDate();

        // Create the checklist
        $checklist = Survey::create([
            'survey_template_id' => $template->id,
            'title' => $template->title.' - '.now()->format('Y-m-d'),
            'description' => $template->description,
            'type' => SurveyType::INTERNAL_CHECKLIST,
            'status' => SurveyStatus::DRAFT,
            'assigned_to_id' => $template->default_assignee_id,
            'due_date' => $dueDate,
            'created_by_id' => $template->created_by_id,
        ]);

        // Update template with next due date
        $nextDueDate = $template->calculateNextDueDate($dueDate ?? now());
        $template->update([
            'last_checklist_generated_at' => now(),
            'next_checklist_due_at' => $nextDueDate,
        ]);

        return $checklist;
    }
}
