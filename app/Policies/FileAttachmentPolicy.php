<?php

namespace App\Policies;

use App\Models\Control;
use App\Models\FileAttachment;
use App\Models\User;
use Illuminate\Support\Str;

class FileAttachmentPolicy
{
    protected string $model = Control::class;

    //    public function viewAny(User $user): bool
    //    {
    //        return $user->can('Delete '.Str::plural(class_basename($this->model)));
    //    }

    public function view(User $user, FileAttachment $attachment): bool
    {
        return $user->can('View '.Str::plural(class_basename($this->model))) || $attachment->uploaded_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.Str::plural(class_basename($this->model)));
    }

    public function update(User $user, FileAttachment $attachment): bool
    {
        return $user->can('Update '.Str::plural(class_basename($this->model))) || $attachment->uploaded_by === $user->id;
    }

    public function delete(User $user, FileAttachment $attachment): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model))) || $attachment->uploaded_by === $user->id;
    }
}
