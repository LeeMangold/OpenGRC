<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Cache;
use App\Filament\Forms\Components\PermissionMatrix;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class RolePermissionMatrix extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $title = 'Role & Permission Matrix';
    protected static ?string $navigationLabel = 'Roles & Permissions';
    protected static ?int $navigationSort = 81;
    protected static string $view = 'filament.pages.role-permission-matrix';

    public ?array $data = [];
    public Collection $roles;
    public Collection $permissions;
    
    public function mount(): void
    {
        $this->roles = Role::all();
        $this->permissions = Permission::all();
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        PermissionMatrix::make('permissions')
                            ->roles($this->roles)
                            ->permissions($this->permissions)
                            ->columnSpanFull()
                    ])
                    ->columns(1)
            ])
            ->statePath('data');
    }

    public function togglePermission($roleId, $permissionId): void
    {
        $role = Role::findOrFail($roleId);
        $permission = Permission::findOrFail($permissionId);

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
            Notification::make()
                ->title('Permission Removed')
                ->body("Removed '{$permission->name}' permission from role '{$role->name}'")
                ->success()
                ->send();
        } else {
            $role->givePermissionTo($permission);
            Notification::make()
                ->title('Permission Added')
                ->body("Added '{$permission->name}' permission to role '{$role->name}'")
                ->success()
                ->send();
        }
        
        Cache::forget('spatie.permission.cache');
    }

    public function hasPermission(Role $role, Permission $permission): bool
    {
        return $role->hasPermissionTo($permission);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editRoles')
                ->label('View and Edit Role Definitions')
                ->url(route('filament.admin.resources.roles.index'))
                ->color('gray')
                ->icon('heroicon-m-pencil-square'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view-any permission') && auth()->user()->can('view-any role');
    }
} 