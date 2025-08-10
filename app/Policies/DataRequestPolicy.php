<?php

namespace App\Policies;

use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\DataRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire;

class DataRequestPolicy
{
    protected string $model = DataRequest::class;

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

    public function update(User $user, DataRequest $dataRequest): bool
    {
        return $user->can('Update '.Str::plural(class_basename($this->model))) && ($this->isOwner() || $this->isMember());
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete '.Str::plural(class_basename($this->model))) && ($this->isOwner() || $this->isMember());
    }

    private function isOwner(): bool
    {
        $type = explode('/', Livewire::originalPath())[1];
        $audit_id = null;

        if ($type === 'audits') {
            $audit_id = explode('/', Livewire::originalPath())[2] ?? null;
        } elseif ($type == 'audit-items') {
            $audit_item_id = explode('/', Livewire::originalPath())[2] ?? null;
            $audit_id = AuditItem::find($audit_item_id)->audit_id;
        }

        if (! $audit_id) {
            return false;
        }

        $audit = Audit::find($audit_id);

        return $audit && $audit->manager_id === auth()->id();
    }

    private function isMember(): bool
    {
        $type = explode('/', Livewire::originalPath())[1];
        $audit_id = null;

        if ($type === 'audits') {
            $audit_id = explode('/', Livewire::originalPath())[2] ?? null;
        } elseif ($type == 'audit-items') {
            $audit_item_id = explode('/', Livewire::originalPath())[2] ?? null;
            $audit_id = AuditItem::find($audit_item_id)->audit_id;
        }

        if (! $audit_id) {
            return false;
        }

        $audit = Audit::find($audit_id);

        return $audit && $audit->members->contains(auth()->id());
    }
}
