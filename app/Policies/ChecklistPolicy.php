<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class ChecklistPolicy
{
    /**
     * This policy uses the Survey model but checks "Checklists" permissions.
     * Checklists are Surveys with type = INTERNAL_CHECKLIST.
     */
    protected string $permissionEntity = 'Checklists';

    public function viewAny(User $user): bool
    {
        return $user->can('List '.$this->permissionEntity);
    }

    public function view(User $user, Survey $survey): bool
    {
        return $user->can('Read '.$this->permissionEntity);
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.$this->permissionEntity);
    }

    public function update(User $user, Survey $survey): bool
    {
        return $user->can('Update '.$this->permissionEntity);
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $user->can('Delete '.$this->permissionEntity);
    }

    public function restore(User $user, Survey $survey): bool
    {
        return $user->can('Update '.$this->permissionEntity);
    }

    public function forceDelete(User $user, Survey $survey): bool
    {
        return $user->can('Delete '.$this->permissionEntity);
    }
}
