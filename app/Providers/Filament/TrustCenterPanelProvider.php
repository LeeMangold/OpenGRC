<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class TrustCenterPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('trustcenter')
            ->path('trust')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName($this->getTrustCenterName())
            ->discoverResources(in: app_path('Filament/TrustCenter/Resources'), for: 'App\\Filament\\TrustCenter\\Resources')
            ->discoverPages(in: app_path('Filament/TrustCenter/Pages'), for: 'App\\Filament\\TrustCenter\\Pages')
            ->discoverWidgets(in: app_path('Filament/TrustCenter/Widgets'), for: 'App\\Filament\\TrustCenter\\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
        // Note: No authMiddleware - this panel is publicly accessible
    }

    private function getTrustCenterName(): string
    {
        try {
            \DB::connection()->getPdo();

            $companyName = setting('trust_center.company_name', '');
            $trustCenterName = setting('trust_center.name', 'Trust Center');

            if (! empty($companyName)) {
                return $companyName.' '.$trustCenterName;
            }

            return $trustCenterName;
        } catch (\Exception $e) {
            return 'Trust Center';
        }
    }
}
