<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                                ->content(config('app.url').'/auth/azure/callback'),
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

            // Okta Authentication
            Section::make('Okta Authentication')
                ->schema([
                    Toggle::make('auth.okta.enabled')
                        ->label('Enable Okta Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.okta.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->required(fn ($get) => $get('auth.okta.enabled')),
                            TextInput::make('auth.okta.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->required(fn ($get) => $get('auth.okta.enabled'))
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        $component->state(Crypt::decryptString($state));
                                    }
                                }),
                            TextInput::make('auth.okta.base_url')
                                ->label('Base URL')
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->required(fn ($get) => $get('auth.okta.enabled')),
                            Placeholder::make('auth.okta.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->content(config('app.url').'/auth/okta/callback'),
                            Toggle::make('auth.okta.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.okta.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Okta.'),
                            Select::make('auth.okta.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.okta.enabled') && $get('auth.okta.auto_provision'))
                                ->required(fn ($get) => $get('auth.okta.enabled') && $get('auth.okta.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),

            // Google Authentication
            Section::make('Google Authentication')
                ->schema([
                    Toggle::make('auth.google.enabled')
                        ->label('Enable Google Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.google.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->required(fn ($get) => $get('auth.google.enabled')),
                            TextInput::make('auth.google.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->required(fn ($get) => $get('auth.google.enabled'))
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        $component->state(Crypt::decryptString($state));
                                    }
                                }),
                            Placeholder::make('auth.google.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->content(config('app.url').'/auth/google/callback'),
                            Toggle::make('auth.google.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.google.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Google.'),
                            Select::make('auth.google.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.google.enabled') && $get('auth.google.auto_provision'))
                                ->required(fn ($get) => $get('auth.google.enabled') && $get('auth.google.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),

            // Auth0 Authentication
            Section::make('Auth0 Authentication')
                ->schema([
                    Toggle::make('auth.auth0.enabled')
                        ->label('Enable Auth0 Authentication')
                        ->default(false)
                        ->live(),
                    Grid::make(2)
                        ->schema([
                            TextInput::make('auth.auth0.client_id')
                                ->label('Client ID')
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->required(fn ($get) => $get('auth.auth0.enabled')),
                            TextInput::make('auth.auth0.client_secret')
                                ->label('Client Secret')
                                ->password()
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->required(fn ($get) => $get('auth.auth0.enabled'))
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                ->afterStateHydrated(function (TextInput $component, $state) {
                                    if (filled($state)) {
                                        $component->state(Crypt::decryptString($state));
                                    }
                                }),
                            TextInput::make('auth.auth0.domain')
                                ->label('Domain')
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->required(fn ($get) => $get('auth.auth0.enabled')),
                            Placeholder::make('auth.auth0.redirect')
                                ->label('Redirect URL')
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->content(config('app.url').'/auth/auth0/callback'),
                            Toggle::make('auth.auth0.auto_provision')
                                ->live()
                                ->label('Auto Provision Users')
                                ->default(false)
                                ->visible(fn ($get) => $get('auth.auth0.enabled'))
                                ->helperText('If enabled, users will be automatically provisioned in the system when they login via Auth0.'),
                            Select::make('auth.auth0.role')
                                ->label('Role')
                                ->options(Role::all()->pluck('name', 'id'))
                                ->visible(fn ($get) => $get('auth.auth0.enabled') && $get('auth.auth0.auto_provision'))
                                ->required(fn ($get) => $get('auth.auth0.enabled') && $get('auth.auth0.auto_provision'))
                                ->helperText('The role to assign to users when they are auto-provisioned.'),
                        ]),
                ]),
        ];
    }
}
