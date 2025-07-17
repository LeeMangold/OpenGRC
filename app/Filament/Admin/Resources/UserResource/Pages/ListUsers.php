<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Mail\UserCreatedMail;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Invite New User')
                ->form([
                    TextInput::make('name')
                        ->label('Name')
                        ->required(),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->unique('users', 'email')
                        ->required(),
                    Select::make('role')
                        ->relationship('roles', 'name')
                        ->label('Role')
                        ->options(
                            Role::all()->pluck('name', 'name')
                        )
                        ->required(),
                ])
                ->action(function (array $data): void {
                    // Create the new user
                    $password = UserResource::createDefaultPassword();
                    $user = User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => bcrypt($password),
                    ]);

                    $roleId = $data['role'];
                    $user->syncRoles([$roleId]);

                    // Send the email with the password to the user
                    try {
                        Mail::to($data['email'])->send(new UserCreatedMail($data['email'], $data['name'], $password));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send invitation email. User still created.'.$e->getMessage())
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('User created and invitation sent!')
                        ->success()
                        ->send();

                }),
        ];

    }
}
