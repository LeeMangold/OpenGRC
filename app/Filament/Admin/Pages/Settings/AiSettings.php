<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\AiQuotaSchema;
use Closure;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-variable';

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

    public function schema(): array|Closure
    {
        $isLocked = setting('storage.locked') === 'true';

        return [
            Tabs::make('AiSettings')
                ->tabs([
                    Tabs\Tab::make('Configuration')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('AI Configuration')
                                ->schema([
                                    Toggle::make('ai.enabled')
                                        ->label('Enable AI Suggestions')
                                        ->default(false),
                                    TextInput::make('ai.openai_key')
                                        ->label('OpenAI API Key (Optional)')
                                        ->disabled($isLocked)
                                        ->password()
                                        ->placeholder(fn () => filled(setting('ai.openai_key')) ? '••••••••' : null)
                                        ->helperText(fn () => filled(setting('ai.openai_key'))
                                            ? 'API key is stored securely. Leave blank to keep current key.'
                                            : 'The API key for OpenAI')
                                        ->dehydrateStateUsing(function ($state) {
                                            // If blank, keep the existing encrypted key
                                            if (! filled($state)) {
                                                return setting('ai.openai_key');
                                            }

                                            // Encrypt the new key
                                            return Crypt::encryptString($state);
                                        })
                                        ->afterStateHydrated(function (TextInput $component, $state) {
                                            // Never populate the field with the actual key
                                            // This prevents the key from appearing in the Livewire payload
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
                    Tabs\Tab::make('Quota Usage')
                        ->icon('heroicon-o-chart-bar')
                        ->schema(AiQuotaSchema::schema()),
                ]),
        ];
    }
}
