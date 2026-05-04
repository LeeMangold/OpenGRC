<?php

namespace App\Filament\Pages;

use App\Models\FccComplianceEvent;
use App\Models\FccDeadline;
use App\Models\FccLicense;
use App\Models\FccLicenseRuleStatus;
use App\Models\FccRule;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Compliance at a Glance';

    protected static ?int $navigationSort = -2;

    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.pages.fcc-dashboard';

    protected function getViewData(): array
    {
        // Cap at 25 for the dashboard table; the full list is one click
        // away on /app/fcc-licenses. After fcc:import-bulk we may have
        // ~30K stations, so unbounded ::get() would render 30K <tr>s.
        $totalLicenses = FccLicense::query()->count();
        $licenses = FccLicense::query()
            ->orderByRaw("CASE status WHEN 'non_compliant' THEN 0 WHEN 'at_risk' THEN 1 WHEN 'expiring_soon' THEN 2 ELSE 3 END")
            ->orderBy('call_sign')
            ->limit(25)
            ->get();

        $statusCounts = FccLicenseRuleStatus::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $compliant = (int) ($statusCounts['compliant'] ?? 0);
        $atRisk = (int) ($statusCounts['at_risk'] ?? 0);
        $nonCompliant = (int) ($statusCounts['non_compliant'] ?? 0);
        $totalRules = max(1, $compliant + $atRisk + $nonCompliant);
        $overallPct = round(($compliant / $totalRules) * 100, 1);

        $categoryRows = collect();
        $byCat = FccRule::query()->get()->groupBy('category');
        foreach ($byCat as $category => $rules) {
            $ids = $rules->pluck('id');
            $cs = FccLicenseRuleStatus::query()
                ->whereIn('fcc_rule_id', $ids)
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status');

            $c = (int) ($cs['compliant'] ?? 0);
            $a = (int) ($cs['at_risk'] ?? 0);
            $n = (int) ($cs['non_compliant'] ?? 0);
            $tot = max(1, $c + $a + $n);
            $categoryRows->push([
                'label' => $this->formatCategory($category),
                'compliant' => $c,
                'at_risk' => $a,
                'non_compliant' => $n,
                'percent' => round(($c / $tot) * 100, 1),
            ]);
        }
        $categoryRows = $categoryRows->sortByDesc('compliant')->values();

        $topNonCompliant = FccRule::query()
            ->whereHas('statuses', fn ($q) => $q->whereIn('status', ['non_compliant', 'at_risk']))
            ->withCount([
                'statuses as affected_count' => fn ($q) => $q->whereIn('status', ['non_compliant', 'at_risk']),
            ])
            ->orderByDesc('affected_count')
            ->limit(5)
            ->get();

        $deadlines = FccDeadline::query()
            ->whereIn('status', ['upcoming', 'due_soon', 'overdue'])
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $activity = FccComplianceEvent::query()
            ->orderByDesc('occurred_at')
            ->limit(6)
            ->get();

        return [
            'licenses' => $licenses,
            'totalLicenses' => $totalLicenses,
            'totalRules' => $compliant + $atRisk + $nonCompliant,
            'compliant' => $compliant,
            'atRisk' => $atRisk,
            'nonCompliant' => $nonCompliant,
            'overallPct' => $overallPct,
            'categoryRows' => $categoryRows,
            'totalCompliantPct' => $overallPct,
            'totalAtRiskPct' => round(($atRisk / $totalRules) * 100, 1),
            'totalNonPct' => round(($nonCompliant / $totalRules) * 100, 1),
            'topNonCompliant' => $topNonCompliant,
            'deadlines' => $deadlines,
            'activity' => $activity,
        ];
    }

    private function formatCategory(string $key): string
    {
        return match ($key) {
            'technical_standards' => 'Technical Standards',
            'operational_rules' => 'Operational Rules',
            'eas_requirements' => 'EAS Requirements',
            'public_file_rules' => 'Public File Rules',
            'ownership_control' => 'Ownership / Control',
            'reporting_requirements' => 'Reporting Requirements',
            default => str($key)->headline()->toString(),
        };
    }
}
