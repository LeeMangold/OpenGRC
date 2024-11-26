<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class PermissionPolicy
{
    protected string $model = Role::class;

    public function viewAny(User $user): bool
    {
        return $user->can('Manage Users');
    }

    public function view(User $user): bool
    {
        return $user->can('Manage Users');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return $user->can('Manage Users');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
