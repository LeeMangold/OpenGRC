<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;

class AuthenticationSchema
{
    public static function schema(): array
    {
        return [
            // Azure Authentication
            Section::make('Azure Authentication')
                ->schema([
                    Toggle::make('auth.azure.enabled')
                        ->label('Enable Azure Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)                                    
                        ->schema([
                            TextInput::make('auth.azure.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->required(fn ($get) => $get('auth.azure.enabled')),
                            TextInput::make('auth.azure.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->required(fn ($get) => $get('auth.azure.enabled'))
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        $component->state(Crypt::decryptString($state));
                                    }
                                }),
                            TextInput::make('auth.azure.tenant')
                                ->label('Tenant')
                                ->placeholder('common')
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->required(fn ($get) => $get('auth.azure.enabled')),
                            Placeholder::make('auth.azure.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->content(config('app.url') . '/auth/azure/callback'),
                            Toggle::make('auth.azure.auto_provision')
                                ->live()    
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Azure.'),
                            Select::make('auth.azure.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.azure.enabled') && $get('auth.azure.auto_provision'))
                                ->required(fn ($get) => $get('auth.azure.enabled') && $get('auth.azure.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),
        ];
    }
} 