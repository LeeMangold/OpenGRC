<?php

namespace App\Policies;

use App\Models\Bundle;
use App\Models\User;

class BundlePolicy
{
    protected string $model = Bundle::class;

    public function viewAny(User $user): bool
    {
        return $user->can('View Bundles') || $user->can('Manage Bundles');
    }

    public function view(User $user): bool
    {
        return $user->can('View Bundles') || $user->can('Manage Bundles');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
