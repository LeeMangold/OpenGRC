<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\RolePermissionMatrix;
use DB;
use Exception;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use MangoldSecurity\FilamentSettings\SettingsPlugin;
use Modules\DataManager\Filament\DataManagerPlugin;

class AdminPanelProvider extends PanelProvider
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
        return $panel
            ->id('admin')
            ->path('admin')
            ->loginRouteSlug('login')
            ->colors([
                'primary' => Color::Slate,
            ])
            ->brandName(name: 'OpenGRC Admin')
            ->viteTheme('resources/css/filament/app/theme.css')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->globalSearch(true)
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(false)
            ->viteTheme('resources/css/filament/app/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->pages([
                RolePermissionMatrix::class,
            ])
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
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
                    ),
                SettingsPlugin::make()
                    ->pages([
                        \App\Filament\Admin\Pages\Settings\Settings::class,
                        \App\Filament\Admin\Pages\Settings\StorageSettings::class,
                        \App\Filament\Admin\Pages\Settings\MailSettings::class,
                        \App\Filament\Admin\Pages\Settings\AiSettings::class,
                        \App\Filament\Admin\Pages\Settings\ReportSettings::class,
                        \App\Filament\Admin\Pages\Settings\SecuritySettings::class,
                        \App\Filament\Admin\Pages\Settings\AuthenticationSettings::class,
                        \App\Filament\Admin\Pages\Settings\VendorPortalSettings::class,
                        \App\Filament\Admin\Pages\Settings\TrustCenterSettings::class,
                    ]),
                DataManagerPlugin::make(),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => Blade::render("@livewire('multi-window-inactivity-guard')")
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('components.session-expiration-handler')
            )
            ->navigationItems([
                NavigationItem::make('Back to OpenGRC')
                    ->url('/app', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-arrow-left'),
            ]);
    }
}
