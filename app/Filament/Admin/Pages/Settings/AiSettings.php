<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Enums\AiProvider;
use App\Filament\Admin\Pages\Settings\Schemas\AiQuotaSchema;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;

class AiSettings extends BaseSettings
{
    /**
     * Check if Passport encryption keys are configured.
     */
    protected function passportKeysConfigured(): bool
    {
        // Check environment variables first
        if (config('passport.private_key') && config('passport.public_key')) {
            return true;
        }

        // Check for key files
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');

        return File::exists($privateKeyPath) && File::exists($publicKeyPath);
    }

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-variable';

    public static function canAccess(): bool
    {
        if (auth()->check() && auth()->user()->can('Manage Preferences')) {
            return true;
        }

        return false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings.ai_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('AiSettings')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Configuration')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('AI Configuration')
                                    ->schema([
                                        Toggle::make('ai.enabled')
                                            ->label('Enable AI Suggestions')
                                            ->default(false),
                                        Select::make('ai.provider')
                                            ->label('AI Provider')
                                            ->options(
                                                collect(AiProvider::cases())
                                                    ->mapWithKeys(fn (AiProvider $provider) => [$provider->value => $provider->getLabel()])
                                                    ->toArray()
                                            )
                                            ->default(AiProvider::OpenAI->value)
                                            ->live()
                                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                                if ($state) {
                                                    $provider = AiProvider::tryFrom($state);
                                                    if ($provider) {
                                                        $set('ai.model', $provider->getDefaultModel());
                                                    }
                                                }
                                            })
                                            ->helperText('Select the AI provider to use for suggestions'),
                                        Select::make('ai.model')
                                            ->label('Model')
                                            ->options(function (Get $get): array {
                                                $providerValue = $get('ai.provider');
                                                $provider = $providerValue ? AiProvider::tryFrom($providerValue) : AiProvider::OpenAI;

                                                return $provider?->getModels() ?? [];
                                            })
                                            ->default(AiProvider::OpenAI->getDefaultModel())
                                            ->helperText('Select the model to use for AI suggestions'),
                                    ]),
                                Section::make('OpenAI')
                                    ->description('Configure OpenAI API access')
                                    ->collapsed(fn (Get $get): bool => $get('ai.provider') !== AiProvider::OpenAI->value)
                                    ->schema([
                                        TextInput::make('ai.openai_key')
                                            ->label('API Key')
                                            ->password()
                                            ->placeholder(function () {
                                                if (filled(setting('ai.openai_key'))) {
                                                    return '••••••••';
                                                }
                                                if (filled(config('ai.keys.openai'))) {
                                                    return 'Using key from .env';
                                                }

                                                return null;
                                            })
                                            ->helperText(function () {
                                                $hasStoredKey = filled(setting('ai.openai_key'));
                                                $hasEnvKey = filled(config('ai.keys.openai'));

                                                if ($hasStoredKey && $hasEnvKey) {
                                                    return 'API key is stored. Leave blank to clear and use the key from .env instead.';
                                                }
                                                if ($hasStoredKey) {
                                                    return 'API key is stored. Leave blank to clear it.';
                                                }
                                                if ($hasEnvKey) {
                                                    return 'API key provided via OPENAI_API_KEY in .env. Leave blank to use it, or enter a key here to override.';
                                                }

                                                return 'Enter your OpenAI API key';
                                            })
                                            ->dehydrateStateUsing(function ($state) {
                                                // If blank, clear the stored key (env fallback will be used)
                                                if (! filled($state)) {
                                                    return null;
                                                }

                                                return Crypt::encryptString($state);
                                            })
                                            ->afterStateHydrated(function (TextInput $component, $state) {
                                                $component->state(null);
                                            }),
                                    ]),
                                Section::make('DigitalOcean')
                                    ->description('Configure DigitalOcean GenAI API access')
                                    ->collapsed(fn (Get $get): bool => $get('ai.provider') !== AiProvider::DigitalOcean->value)
                                    ->schema([
                                        TextInput::make('ai.digitalocean_key')
                                            ->label('API Key')
                                            ->password()
                                            ->placeholder(function () {
                                                if (filled(setting('ai.digitalocean_key'))) {
                                                    return '••••••••';
                                                }
                                                if (filled(config('ai.keys.digitalocean'))) {
                                                    return 'Using key from .env';
                                                }

                                                return null;
                                            })
                                            ->helperText(function () {
                                                $hasStoredKey = filled(setting('ai.digitalocean_key'));
                                                $hasEnvKey = filled(config('ai.keys.digitalocean'));

                                                if ($hasStoredKey && $hasEnvKey) {
                                                    return 'API key is stored. Leave blank to clear and use the key from .env instead.';
                                                }
                                                if ($hasStoredKey) {
                                                    return 'API key is stored. Leave blank to clear it.';
                                                }
                                                if ($hasEnvKey) {
                                                    return 'API key provided via DIGITALOCEAN_AI_KEY in .env. Leave blank to use it, or enter a key here to override.';
                                                }

                                                return 'Enter your DigitalOcean API key';
                                            })
                                            ->dehydrateStateUsing(function ($state) {
                                                // If blank, clear the stored key (env fallback will be used)
                                                if (! filled($state)) {
                                                    return null;
                                                }

                                                return Crypt::encryptString($state);
                                            })
                                            ->afterStateHydrated(function (TextInput $component, $state) {
                                                $component->state(null);
                                            }),
                                    ]),
                                Section::make('MCP Server')
                                    ->description(new HtmlString(
                                        'Model Context Protocol (MCP) allows AI assistants like Claude to interact with OpenGRC data. '.
                                        '<a href="https://docs.opengrc.com/mcp-server/" target="_blank" class="text-primary-600 hover:underline">Learn more</a>'
                                    ))
                                    ->schema([
                                        Toggle::make('mcp.enabled')
                                            ->label('Enable MCP Server')
                                            ->helperText(fn () => $this->passportKeysConfigured()
                                                ? 'When enabled, authenticated API clients can access OpenGRC via the MCP protocol using OAuth 2.1'
                                                : new HtmlString('<span class="text-danger-600 dark:text-danger-400">Passport encryption keys are not configured. Run <code class="bg-gray-100 dark:bg-gray-800 px-1 rounded">php artisan passport:keys</code> to generate them.</span>'))
                                            ->disabled(fn () => ! $this->passportKeysConfigured())
                                            ->dehydrateStateUsing(function ($state) {
                                                // Prevent enabling if keys are not configured
                                                if ($state && ! $this->passportKeysConfigured()) {
                                                    return false;
                                                }

                                                return $state;
                                            })
                                            ->default(false),
                                    ]),
                                Section::make('OAuth 2.1 Endpoints')
                                    ->description('These endpoints are used by MCP clients to authenticate via OAuth 2.1')
                                    ->collapsed()
                                    ->schema([
                                        Placeholder::make('oauth_discovery')
                                            ->label('OAuth Discovery')
                                            ->content(fn () => new HtmlString('<code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">'.url('/.well-known/oauth-authorization-server').'</code>')),
                                        Placeholder::make('oauth_authorize')
                                            ->label('Authorization Endpoint')
                                            ->content(fn () => new HtmlString('<code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">'.url('/oauth/authorize').'</code>')),
                                        Placeholder::make('oauth_token')
                                            ->label('Token Endpoint')
                                            ->content(fn () => new HtmlString('<code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">'.url('/oauth/token').'</code>')),
                                        Placeholder::make('oauth_register')
                                            ->label('Dynamic Client Registration')
                                            ->content(fn () => new HtmlString('<code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">'.url('/oauth/register').'</code>')),
                                        Placeholder::make('mcp_endpoint')
                                            ->label('MCP Endpoint')
                                            ->content(fn () => new HtmlString('<code class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">'.url('/mcp/opengrc').'</code>')),
                                    ]),
                            ]),
                        Tab::make('Quota Usage')
                            ->icon('heroicon-o-chart-bar')
                            ->schema(AiQuotaSchema::schema()),
                    ]),
            ]);
    }
}
