<?php

namespace App\Policies;

use App\Models\Control;
use App\Models\User;
use Illuminate\Support\Str;

class FileAttachmentPolicy
{
    protected string $model = Control::class;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return true;
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model)));
    }
}
