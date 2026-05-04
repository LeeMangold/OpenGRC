<?php

namespace App\Filament\Widgets;

use App\Models\FccLicenseRuleStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FccTopNonCompliantRulesWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Non-Compliant Rules';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FccLicenseRuleStatus::query()
                    ->whereIn('status', ['non_compliant', 'at_risk'])
                    ->selectRaw('fcc_rule_id, COUNT(*) as affected_count, MAX(status) as worst_status')
                    ->groupBy('fcc_rule_id')
                    ->orderByDesc('affected_count')
                    ->with('rule')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('rule.rule_number')->label('FCC Rule')->weight('bold'),
                TextColumn::make('rule.title')->label('Description')->limit(45),
                TextColumn::make('affected_count')->label('Affected')->alignRight(),
                TextColumn::make('rule.severity')->label('Severity')->badge()
                    ->color(fn ($state) => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        default => 'gray',
                    }),
            ])
            ->paginated(false);
    }
}
