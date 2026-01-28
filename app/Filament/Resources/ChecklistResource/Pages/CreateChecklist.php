<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Enums\SurveyType;
use App\Filament\Resources\ChecklistResource;
use App\Models\SurveyTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateChecklist extends CreateRecord
{
    protected static string $resource = ChecklistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();
        $data['type'] = SurveyType::INTERNAL_CHECKLIST;

        // Set default assignee from template if not already set
        if (empty($data['assigned_to_id']) && ! empty($data['survey_template_id'])) {
            $template = SurveyTemplate::find($data['survey_template_id']);
            if ($template && $template->default_assignee_id) {
                $data['assigned_to_id'] = $template->default_assignee_id;
            }
        }

        return $data;
    }
}
