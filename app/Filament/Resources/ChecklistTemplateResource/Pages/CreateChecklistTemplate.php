<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Enums\SurveyType;
use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateChecklistTemplate extends CreateRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();
        $data['type'] = SurveyType::INTERNAL_CHECKLIST;

        return $data;
    }
}
