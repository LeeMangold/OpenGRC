<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use DB;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Provider;
use Exception;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

class AppPanelProvider extends PanelProvider
{
    private function getSessionTimeout(): int
    {
        try {
            // Check if database is connected
            DB::connection()->getPdo();

            return setting('security.session_timeout', 15);
        } catch (Exception $e) {
            // Return default value if database is not available
            return 15;
        }
    }

    public function panel(Panel $panel): Panel
    {
        $socialProviders = [];

        // Build social providers array using FilamentSocialite v3 API
        // Check if settings service is available (may not be during early boot)
        if (app()->bound('setting')) {
            try {
                // Check if database is available before accessing settings
                DB::connection()->getPdo();

                if (setting('auth.okta.enabled')) {
                    $socialProviders[] = Provider::make('okta')
                        ->label('Okta')
                        ->icon('heroicon-o-lock-closed')
                        ->color(Color::Slate);
                }

                if (setting('auth.microsoft.enabled')) {
                    $socialProviders[] = Provider::make('microsoft')
                        ->label('Microsoft')
                        ->icon('heroicon-o-window')
                        ->color(Color::Slate);
                }

                if (setting('auth.azure.enabled')) {
                    $socialProviders[] = Provider::make('azure')
                        ->label('Azure AD')
                        ->icon('heroicon-o-cloud')
                        ->color(Color::Slate);
                }

                if (setting('auth.google.enabled')) {
                    $socialProviders[] = Provider::make('google')
                        ->label('Google')
                        ->icon('heroicon-o-globe-alt')
                        ->color(Color::Slate);
                }

                if (setting('auth.auth0.enabled')) {
                    $socialProviders[] = Provider::make('auth0')
                        ->label('Auth0')
                        ->icon('heroicon-o-lock-closed')
                        ->color(Color::Slate);
                }

            } catch (Exception $e) {
                // Log the error to help debug SSO issues
                logger()->warning('SSO providers could not be loaded: '.$e->getMessage());
            }
        }

        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->loginRouteSlug('login')
            ->colors([
                'primary' => Color::hex('#fbbf24'),
                'gray' => Color::hex('#0c1f3d'),
                'info' => Color::hex('#22d3ee'),
                'success' => Color::hex('#22c55e'),
                'warning' => Color::hex('#f59e0b'),
                'danger' => Color::hex('#ef4444'),
            ])
            ->brandName('OpenGRC FCC Compliance')
            ->defaultThemeMode(\Filament\Enums\ThemeMode::Dark)
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->globalSearch(true)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->viteTheme('resources/css/filament/app/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\FccComplianceOverviewWidget::class,
                \App\Filament\Widgets\FccUpcomingDeadlinesWidget::class,
            ])
            ->plugins([
                FilamentApexChartsPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: false,
                        slug: 'me',
                        navigationGroup: 'Settings',
                    )
                    ->enableTwoFactorAuthentication(
                        force: false,
                    )
                    ->passwordUpdateRules(
                        rules: [Password::default()->mixedCase()->uncompromised(3)->min(12)],
                    )
                    ->enableSanctumTokens(),
                FilamentSocialitePlugin::make()
                    ->providers($socialProviders),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => Blade::render("@livewire('multi-window-inactivity-guard')")
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('components.session-expiration-handler')
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.app.sidebar-bottom-links')
            )
            ->renderHook(
                PanelsRenderHook::FOOTER,
                fn () => view('filament.footer.fcc-compliance-strip')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('filament.topbar.fcc-alerts-badge')
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => view('filament.topbar.fcc-export-button')
            )
            ->navigationGroups([
                'FCC Compliance',
                'FCC Compliance Review',
                'Documents & Reports',
                'Foundations',
                'Entities',
                'Admin',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
