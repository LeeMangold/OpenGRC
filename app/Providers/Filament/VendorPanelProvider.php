<?php

namespace App\Providers\Filament;

use App\Filament\Vendor\Resources\SurveyResource;
use App\Http\Middleware\RequireVendorPassword;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
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
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class VendorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('vendor')
            ->path('portal')
            ->authGuard('vendor')
            ->authPasswordBroker('vendor_users')
            ->login(\App\Filament\Vendor\Pages\Auth\Login::class)
            ->passwordReset()
            ->emailVerification()
            ->profile()
            ->colors([
                'primary' => Color::Teal,
            ])
            ->brandName($this->getPortalName())
            ->discoverResources(in: app_path('Filament/Vendor/Resources'), for: 'App\\Filament\\Vendor\\Resources')
            ->discoverPages(in: app_path('Filament/Vendor/Pages'), for: 'App\\Filament\\Vendor\\Pages')
            ->discoverWidgets(in: app_path('Filament/Vendor/Widgets'), for: 'App\\Filament\\Vendor\\Widgets')
            ->widgets([])
            ->homeUrl(fn () => SurveyResource::getUrl())
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
                RequireVendorPassword::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('components.session-expiration-handler')
            );
    }

    private function getPortalName(): string
    {
        try {
            \DB::connection()->getPdo();

            return setting('vendor_portal.name', 'Vendor Portal');
        } catch (\Exception $e) {
            return 'Vendor Portal';
        }
    }
}
