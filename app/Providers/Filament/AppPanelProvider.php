<?php

namespace App\Providers\Filament;

use App\Models\Settings;
use App\Models\User;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use JibayMcs\FilamentTour\FilamentTourPlugin;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;
use Outerweb\FilamentSettings\Filament\Plugins\FilamentSettingsPlugin;


class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $socialProviders = [];

        // Comment out or wrap all settings() calls in try-catch
        try {
            if (setting('auth.okta.enabled')) {
                $socialProviders['okta'] = [
                    'label' => 'Okta',
                    'icon' => 'heroicon-o-lock-closed',
                    'color' => 'primary',
                ];
            }

            if (setting('auth.microsoft.enabled')) {
                $socialProviders['microsoft'] = [
                    'label' => 'Microsoft',
                    'icon' => 'heroicon-o-window',
                    'color' => 'primary',
                ];
            }

            if (setting('auth.azure.enabled')) {
                $socialProviders['azure'] = [
                    'label' => 'Azure AD',
                    'icon' => 'heroicon-o-cloud',
                    'color' => 'primary',
                ];
            }

            if (setting('auth.google.enabled')) {
                $socialProviders['google'] = [
                    'label' => 'Google',
                    'icon' => 'heroicon-o-globe-alt',
                    'color' => 'primary',
                ];
            }

            if (setting('auth.auth0.enabled')) {
                $socialProviders['auth0'] = [
                    'label' => 'Auth0',
                    'icon' => 'heroicon-o-lock-closed',
                    'color' => 'primary',
                ];
            }

        } catch (\Exception $e) {
            // Silently fail and use empty social providers during setup
        }

        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->loginRouteSlug('login')
            ->colors([
                'primary' => Color::Slate,
            ])
            ->brandName('OpenGRC')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->globalSearch(true)
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->viteTheme('resources/css/filament/app/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                //                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->plugins([
                FilamentApexChartsPlugin::make(),
                //                FilamentTourPlugin::make(),
                FilamentSettingsPlugin::make()
                ->pages([
                    \App\Filament\Pages\Settings\Settings::class,
                ]),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: false,
                        slug: 'me',
                        navigationGroup: 'Settings',
                    )
                    ->enableTwoFactorAuthentication(
                        force: false, // force the user to enable 2FA before they can use the application (default = false)
                    )
                    ->passwordUpdateRules(
                        rules: [Password::default()->mixedCase()->uncompromised(3)->min(12)],
                    ),
                FilamentSocialitePlugin::make()
                    ->setProviders($socialProviders),
            ],

            )
            ->navigationGroups([
                'Foundations',
                'Entities',
                'Settings',
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
                \App\Http\Middleware\UserActivityMonitor::class,
                \App\Http\Middleware\SessionTimeout::class,
            ])
            ->authGuard('web')
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
