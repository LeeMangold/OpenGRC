<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Collection;

class PermissionMatrix extends Field
{
    protected string $view = 'filament.pages.role-permission-matrix-table';

    protected Collection $matrixRoles;
    protected Collection $matrixPermissions;

    public function roles(Collection $roles): static
    {
        $this->matrixRoles = $roles;
        return $this;
    }

    public function permissions(Collection $permissions): static
    {
        $this->matrixPermissions = $permissions;
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
} 