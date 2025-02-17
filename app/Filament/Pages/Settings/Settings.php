<?php

namespace App\Filament\Pages\Settings;

use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Crypt;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        if (auth()->user()->can('Manage Preferences')) {
            return true;
        }

        return false;
    }

    public static function getNavigationLabel(): string
    {
        return 'General Settings';
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('Settings')
                ->columns(2)
                ->schema([
                    Tabs\Tab::make('General')
                        ->schema([
                            TextInput::make('general.name')
                                ->default('ets')
                                ->minLength(2)
                                ->maxLength(16)
                                ->label('Application Name')
                                ->helperText('The name of your application')
                                ->required(),
                            TextInput::make('general.url')
                                ->default('http://localhost')
                                ->url()
                                ->label('Application URL')
                                ->helperText('The URL of your application')
                                ->required(),
                            TextInput::make('general.repo')
                                ->default('https://repo.opengrc.com')
                                ->url()
                                ->label('Update Repository URL')
                                ->helperText('The URL of the repository to check for content updates')
                                ->required(),
                        ]),
                    Tabs\Tab::make('Mail')
                        ->columns(3)
                        ->label('Mail Settings')
                        ->schema([
                            TextInput::make('mail.host'),
                            TextInput::make('mail.port')
                                ->type('number'),
                            Select::make('mail.encryption')
                                ->options([
                                    'TLS' => 'TLS',
                                    'STARTTLS' => 'STARTTLS',
                                    'none' => 'None',
                                ]),
                            TextInput::make('mail.username'),
                            TextInput::make('mail.password')
                                ->password(),
                            TextInput::make('mail.from')
                                ->label('From Address')
                                ->email()
                                ->helperText('The email address to send emails from'),
                        ]),
                    Tabs\Tab::make('Mail Templates')
                        ->schema([
                            TextInput::make('mail.templates.password_reset_subject')
                                ->label('Password Reset Subject')
                                ->columnSpanFull(),
                            RichEditor::make('mail.templates.password_reset_body')
                                ->label('Password Reset Body')
                                ->columnSpanFull(),
                            TextInput::make('mail.templates.new_account_subject')
                                ->label('New Account Subject')
                                ->columnSpanFull(),
                            RichEditor::make('mail.templates.new_account_body')
                                ->label('New Account Body')
                                ->columnSpanFull(),
                            TextInput::make('mail.templates.evidence_request_subject')
                                ->label('Evidence Request Subject')
                                ->columnSpanFull(),
                            RichEditor::make('mail.templates.evidence_request_body')
                                ->label('Evidence Request Body')
                                ->columnSpanFull(),
                        ]),
                    Tabs\Tab::make('AI Settings')
                        ->schema([
                            Toggle::make('ai.enabled')
                                ->label('Enable AI Suggestions')
                                ->default(false),
                            TextInput::make('ai.openai_key')
                                ->label('OpenAI API Key (Optional)')
                                ->password()
                                ->helperText('The API key for OpenAI')
                                ->default(fn ($state) => filled($state) ? Crypt::decryptString($state) : null)
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null),
                        ]),
                    Tabs\Tab::make('Report Settings')
                        ->schema([
                            FileUpload::make('report.logo')
                                ->label('Report Logo')
                                ->helperText('The logo to display on reports. Be sure to upload a file that is at least 512px wide.')
                                ->acceptedFileTypes(['image/*'])
                                ->disk('public')
                                ->maxFiles(1)
                                ->imagePreviewHeight('150px'),
                        ]),
                    Tabs\Tab::make('Security')
                        ->schema([
                            TextInput::make('security.session_timeout')
                                ->label('Session Timeout (minutes)')
                                ->numeric()
                                ->default(15)
                                ->minValue(1)
                                ->maxValue(1440) // Max 24 hours
                                ->required()
                                ->helperText('Number of minutes before an inactive session expires. Default: 15 minutes'),
                        ]),
                    Tabs\Tab::make('Authentication')
                        ->schema([
                    //         Section::make('Okta Authentication')
                    //             ->schema([
                    //                 Toggle::make('auth.okta.enabled')
                    //                     ->label('Enable Okta Authentication')
                    //                     ->default(false)
                    //                     ->live(),
                    //                 Grid::make(2)
                    //                     ->schema([
                    //                         TextInput::make('auth.okta.client_id')
                    //                             ->label('Client ID')
                    //                             ->visible(fn ($get) => $get('auth.okta.enabled'))
                    //                             ->required(fn ($get) => $get('auth.okta.enabled')),
                    //                         TextInput::make('auth.okta.client_secret')
                    //                             ->label('Client Secret')
                    //                             ->password()
                    //                             ->visible(fn ($get) => $get('auth.okta.enabled'))
                    //                             ->required(fn ($get) => $get('auth.okta.enabled'))
                    //                             ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                    //                             ->dehydrated(fn ($state) => filled($state)),
                    //                         TextInput::make('auth.okta.base_url')
                    //                             ->label('Base URL')
                    //                             ->placeholder('https://your-domain.okta.com')
                    //                             ->visible(fn ($get) => $get('auth.okta.enabled'))
                    //                             ->required(fn ($get) => $get('auth.okta.enabled')),
                    //                     ]),
                    //             ]),

                    //         Section::make('Microsoft Authentication')
                    //             ->schema([
                    //                 Toggle::make('auth.microsoft.enabled')
                    //                     ->label('Enable Microsoft Authentication')
                    //                     ->default(false)
                    //                     ->live(),
                    //                 Grid::make(2)
                    //                     ->schema([
                    //                         TextInput::make('auth.microsoft.client_id')
                    //                             ->label('Client ID')
                    //                             ->visible(fn ($get) => $get('auth.microsoft.enabled'))
                    //                             ->required(fn ($get) => $get('auth.microsoft.enabled')),
                    //                         TextInput::make('auth.microsoft.client_secret')
                    //                             ->label('Client Secret')
                    //                             ->password()
                    //                             ->visible(fn ($get) => $get('auth.microsoft.enabled'))
                    //                             ->required(fn ($get) => $get('auth.microsoft.enabled'))
                    //                             ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                    //                             ->dehydrated(fn ($state) => filled($state)),
                    //                         TextInput::make('auth.microsoft.tenant')
                    //                             ->label('Tenant')
                    //                             ->placeholder('common')
                    //                             ->visible(fn ($get) => $get('auth.microsoft.enabled'))
                    //                             ->required(fn ($get) => $get('auth.microsoft.enabled')),
                    //                     ]),
                    //             ]),

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
                                                ->dehydrated(fn ($state) => filled($state)),
                                            TextInput::make('auth.azure.tenant')
                                                ->label('Tenant')
                                                ->placeholder('common')
                                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                                ->required(fn ($get) => $get('auth.azure.enabled')),
                                            Placeholder::make('auth.azure.redirect')
                                                ->label('Redirect URL')
                                                ->visible(fn ($get) => $get('auth.azure.enabled'))
                                                ->content(config('app.url') . '/auth/azure/callback'),
                                        ]),
                                ]),
                        ]),
                ]),
        ];
    }
}
