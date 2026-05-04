<?php

namespace App\Filament\Widgets;

use App\Models\FccLicense;
use App\Models\FccLicenseRuleStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FccComplianceOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    public function getHeading(): ?string
    {
        return 'Compliance at a Glance';
    }

    protected function getStats(): array
    {
        $licenseCount = FccLicense::query()->count();
        $ruleStatuses = FccLicenseRuleStatus::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $compliant   = (int) ($ruleStatuses['compliant'] ?? 0);
        $atRisk      = (int) ($ruleStatuses['at_risk'] ?? 0);
        $nonCompliant = (int) ($ruleStatuses['non_compliant'] ?? 0);
        $total = max(1, $compliant + $atRisk + $nonCompliant);
        $overall = round(($compliant / $total) * 100, 1);

        $rulesTotal = $compliant + $atRisk + $nonCompliant;

        return [
            Stat::make('Overall Compliance', $overall.'%')
                ->description('Compliant')
                ->color($overall >= 95 ? 'success' : ($overall >= 85 ? 'warning' : 'danger'))
                ->icon('heroicon-o-shield-check'),

            Stat::make('Licenses', (string) $licenseCount)
                ->description('Active authorizations')
                ->color('warning')
                ->icon('heroicon-o-identification'),

            Stat::make('Rule Requirements', number_format($rulesTotal))
                ->description('Total tracked')
                ->color('info')
                ->icon('heroicon-o-scale'),

            Stat::make('Compliant', number_format($compliant))
                ->description(sprintf('%.1f%%', ($compliant / $total) * 100))
                ->color('success')
                ->icon('heroicon-o-check-circle'),

            Stat::make('At Risk', number_format($atRisk))
                ->description(sprintf('%.1f%%', ($atRisk / $total) * 100))
                ->color('warning')
                ->icon('heroicon-o-exclamation-triangle'),

            Stat::make('Non-Compliant', number_format($nonCompliant))
                ->description(sprintf('%.1f%%', ($nonCompliant / $total) * 100))
                ->color('danger')
                ->icon('heroicon-o-shield-exclamation'),
        ];
    }
}
