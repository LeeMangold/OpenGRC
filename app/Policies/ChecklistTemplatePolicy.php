<?php

namespace App\Policies;

use App\Models\SurveyTemplate;
use App\Models\User;

class ChecklistTemplatePolicy
{
    /**
     * This policy uses the SurveyTemplate model but checks "ChecklistTemplates" permissions.
     * Checklist templates are SurveyTemplates with type = INTERNAL_CHECKLIST.
     */
    protected string $permissionEntity = 'ChecklistTemplates';

    public function viewAny(User $user): bool
    {
        return $user->can('List '.$this->permissionEntity);
    }

    public function view(User $user, SurveyTemplate $surveyTemplate): bool
    {
        return $user->can('Read '.$this->permissionEntity);
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.$this->permissionEntity);
    }

    public function update(User $user, SurveyTemplate $surveyTemplate): bool
    {
        return $user->can('Update '.$this->permissionEntity);
    }

    public function delete(User $user, SurveyTemplate $surveyTemplate): bool
    {
        // Cannot delete templates that have checklists
        if ($surveyTemplate->hasChecklists()) {
            return false;
        }

        return $user->can('Delete '.$this->permissionEntity);
    }

    public function restore(User $user, SurveyTemplate $surveyTemplate): bool
    {
        return $user->can('Update '.$this->permissionEntity);
    }

    public function forceDelete(User $user, SurveyTemplate $surveyTemplate): bool
    {
        // Cannot force delete templates that have checklists
        if ($surveyTemplate->hasChecklists()) {
            return false;
        }

        return $user->can('Delete '.$this->permissionEntity);
    }
}
