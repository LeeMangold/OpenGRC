<?php

namespace App\Filament\Widgets;

use App\Models\FccLicenseRuleStatus;
use App\Models\FccRule;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class FccRuleCategoryRollupWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.fcc-rule-category-rollup';

    protected int|string|array $columnSpan = 1;

    public function getCategoryRows(): Collection
    {
        $byCat = FccRule::query()
            ->select('category', 'id')
            ->get()
            ->groupBy('category');

        $rows = collect();
        foreach ($byCat as $category => $rules) {
            $ids = $rules->pluck('id');
            $statuses = FccLicenseRuleStatus::query()
                ->whereIn('fcc_rule_id', $ids)
                ->selectRaw('status, COUNT(*) as c')
                ->groupBy('status')
                ->pluck('c', 'status');

            $compliant = (int) ($statuses['compliant'] ?? 0);
            $atRisk = (int) ($statuses['at_risk'] ?? 0);
            $nonCompliant = (int) ($statuses['non_compliant'] ?? 0);
            $total = max(1, $compliant + $atRisk + $nonCompliant);

            $rows->push([
                'category' => $this->formatCategory($category),
                'compliant' => $compliant,
                'at_risk' => $atRisk,
                'non_compliant' => $nonCompliant,
                'percent' => round(($compliant / $total) * 100, 1),
            ]);
        }

        $totalCompliant = $rows->sum('compliant');
        $totalAtRisk = $rows->sum('at_risk');
        $totalNon = $rows->sum('non_compliant');
        $grandTotal = max(1, $totalCompliant + $totalAtRisk + $totalNon);

        $rows->push([
            'category' => 'TOTAL',
            'compliant' => $totalCompliant,
            'at_risk' => $totalAtRisk,
            'non_compliant' => $totalNon,
            'percent' => round(($totalCompliant / $grandTotal) * 100, 1),
            'is_total' => true,
        ]);

        return $rows;
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
