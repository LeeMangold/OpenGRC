<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class PermissionMatrix extends Field
{
    protected string $view = 'filament.pages.role-permission-matrix-table';

    protected Collection $matrixRoles;
    protected Collection $matrixPermissions;
    protected ?BaseCollection $groupedPermissions = null;

    public function roles(Collection $roles): static
    {
        $this->matrixRoles = $roles;
        return $this;
    }

    public function permissions(Collection $permissions): static
    {
        $this->matrixPermissions = $permissions;
        $this->groupedPermissions = $permissions->groupBy('category')->sortKeys();
        return $this;
    }

    public function getRoles(): Collection
    {
        return $this->matrixRoles;
    }

    public function getPermissions(): Collection
    {
        return $this->matrixPermissions;
    }

    public function getGroupedPermissions(): BaseCollection
    {
        return $this->groupedPermissions;
    }
} 