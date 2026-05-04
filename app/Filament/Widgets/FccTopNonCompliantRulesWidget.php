<?php

namespace App\Filament\Widgets;

use App\Models\FccRule;
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
                FccRule::query()
                    ->withCount([
                        'statuses as affected_count' => fn ($q) => $q->whereIn('status', ['non_compliant', 'at_risk']),
                    ])
                    ->having('affected_count', '>', 0)
                    ->orderByDesc('affected_count')
                    ->limit(8)
            )
            ->columns([
                TextColumn::make('rule_number')->label('FCC Rule')->weight('bold'),
                TextColumn::make('title')->label('Description')->limit(45),
                TextColumn::make('affected_count')->label('Affected')->alignment('right'),
                TextColumn::make('severity')->label('Severity')->badge()
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
