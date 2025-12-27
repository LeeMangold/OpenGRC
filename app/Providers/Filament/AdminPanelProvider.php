<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Rmsramos\Activitylog\ActivitylogPlugin;

class AdminPanelProvider extends PanelProvider
{
    private function getSessionTimeout(): int
    {
        try {
            // Check if database is connected
            \DB::connection()->getPdo();

            return setting('security.session_timeout', 15);
        } catch (\Exception $e) {
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
                'primary' => Color::Amber,
            ])
            ->brandName(name: 'OpenGRC Admin')
            ->viteTheme('resources/css/filament/app/theme.css')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->pages([
                \App\Filament\Admin\Pages\RolePermissionMatrix::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
                \Outerweb\FilamentSettings\Filament\Plugins\FilamentSettingsPlugin::make()
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
                ActivitylogPlugin::make([
                    'enable_cleanup_command' => true,
                    'default_sort_column' => 'created_at',
                ])->authorize(fn () => auth()->check() && auth()->user()->can('View Audit Log')),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => Blade::render("@livewire('multi-window-inactivity-guard')")
            )
            ->navigationItems([
                NavigationItem::make('Back to OpenGRC')
                    ->url('/app', shouldOpenInNewTab: false)
                    ->icon('heroicon-o-arrow-left'),
            ]);
    }
}
