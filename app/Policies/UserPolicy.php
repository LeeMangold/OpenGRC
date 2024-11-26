<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    protected string $model = User::class;

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
        return $user->can('Manage Users');
    }

    public function update(User $user): bool
    {
        return $user->can('Manage Users');
    }

    public function delete(User $user): bool
    {
        return $user->can('Manage Users');
    }
}
