<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
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
                \App\Filament\Admin\Pages\RolePermissionMatrix::class
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
                \App\Http\Middleware\UserActivityMonitor::class,
                \App\Http\Middleware\SessionTimeout::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \Outerweb\FilamentSettings\Filament\Plugins\FilamentSettingsPlugin::make()
                ->pages([
                    \App\Filament\Admin\Pages\Settings\Settings::class,
                ]),
            ])
            ->navigationItems([
                NavigationItem::make('Back to OpenGRC')
                    ->url("/app", shouldOpenInNewTab: false)
                    ->icon('heroicon-o-arrow-left')
            ])
            ->renderHook(
                'panels::body.end',
                fn () => auth()->check() ? view('components.session-timeout-warning') : ''
            );
    }
}
