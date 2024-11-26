<?php

namespace App\Policies;

use App\Models\Implementation;
use App\Models\User;
use Illuminate\Support\Str;

class ImplementationPolicy
{
    protected string $model = Implementation::class;

    public function viewAny(User $user): bool
    {
        return $user->can('List '.Str::plural(class_basename($this->model)));
    }

    public function view(User $user): bool
    {
        return $user->can('Read '.Str::plural(class_basename($this->model)));
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.Str::plural(class_basename($this->model)));
    }

    public function update(User $user): bool
    {
        return $user->can('Edit '.Str::plural(class_basename($this->model)));
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model)));
    }
}
