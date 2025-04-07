<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\FileAttachment;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire;

class FileAttachmentPolicy
{
    protected string $model = Control::class;

    public function viewAny(User $user): bool
    {
        if ($user->can('Read Audits') && ($this->isOwner() || $this->isMember())) {
            return true;
        }

        return false;
    }

    public function view(User $user, FileAttachment $attachment): bool
    {
        if ($user->can('Read Audits') && ($this->isOwner() || $this->isMember())) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('Create '.Str::plural(class_basename($this->model)));
    }

    public function update(User $user, FileAttachment $attachment): bool
    {
        return $this->isOwner() || $this->isMember();
    }

    public function delete(User $user, FileAttachment $attachment): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model)) && ($this->isOwner() || $this->isMember()));
    }

    private function isOwner(): bool
    {
        $type = explode('/', Livewire::originalPath())[1];
        if ($type === 'audits') {
            $audit_id = explode('/', Livewire::originalPath())[2] ?? null;
        } elseif ($type == 'audit-items') {
            $audit_item_id = explode('/', Livewire::originalPath())[2] ?? null;
            $audit_id = AuditItem::find($audit_item_id)->audit_id;
        }

        $audit = Audit::find($audit_id);

        return $audit->manager_id === auth()->id();
    }

    private function isMember(): bool
    {
        $type = explode('/', Livewire::originalPath())[1];
        if ($type === 'audits') {
            $audit_id = explode('/', Livewire::originalPath())[2] ?? null;
        } elseif ($type == 'audit-items') {
            $audit_item_id = explode('/', Livewire::originalPath())[2] ?? null;
            $audit_id = AuditItem::find($audit_item_id)->audit_id;
        }

        $audit = Audit::find($audit_id);

        return $audit->members->contains(auth()->id());
    }
}
