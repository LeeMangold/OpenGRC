<?php

namespace App\Filament\Widgets\TrustCenter;

use App\Enums\TrustLevel;
use App\Models\Certification;
use App\Models\TrustCenterAccessRequest;
use App\Models\TrustCenterContentBlock;
use App\Models\TrustCenterDocument;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrustCenterStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('Total Documents'), TrustCenterDocument::active()->count())
                ->description(__(':public public, :protected protected', [
                    'public' => TrustCenterDocument::active()->where('trust_level', TrustLevel::PUBLIC)->count(),
                    'protected' => TrustCenterDocument::active()->where('trust_level', TrustLevel::PROTECTED)->count(),
                ]))
                ->icon('heroicon-o-document-text')
                ->color('primary'),

            Stat::make(__('Active Certifications'), Certification::active()->count())
                ->description(__(':custom custom certifications', [
                    'custom' => Certification::active()->custom()->count(),
                ]))
                ->icon('heroicon-o-shield-check')
                ->color('success'),

            Stat::make(__('Pending Requests'), TrustCenterAccessRequest::pending()->count())
                ->description(__(':approved approved total', [
                    'approved' => TrustCenterAccessRequest::approved()->count(),
                ]))
                ->icon('heroicon-o-inbox')
                ->color(TrustCenterAccessRequest::pending()->count() > 0 ? 'warning' : 'gray'),

            Stat::make(__('Content Blocks'), TrustCenterContentBlock::enabled()->count().'/'.$totalBlocks = TrustCenterContentBlock::count())
                ->description(__('enabled'))
                ->icon('heroicon-o-squares-2x2')
                ->color('gray'),
        ];
    }
}
