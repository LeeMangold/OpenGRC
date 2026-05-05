<?php

namespace App\Filament\Widgets;

use App\Models\FccComplianceEvent;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FccComplianceActivityWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Compliance Activity';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FccComplianceEvent::query()
                    ->orderByDesc('occurred_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('event_type')->icon(fn ($state) => match ($state) {
                    'technical_review_passed' => 'heroicon-o-check-circle',
                    'eas_test_filed' => 'heroicon-o-shield-check',
                    'public_file_uploaded' => 'heroicon-o-document-arrow-up',
                    'report_generated' => 'heroicon-o-document-text',
                    'power_warning' => 'heroicon-o-exclamation-triangle',
                    default => 'heroicon-o-information-circle',
                })->iconColor(fn ($state) => match ($state) {
                    'technical_review_passed', 'eas_test_filed' => 'success',
                    'power_warning' => 'warning',
                    default => 'info',
                })->label(''),
                TextColumn::make('summary')->wrap(),
                TextColumn::make('actor')->label('Actor')->color('gray'),
                TextColumn::make('occurred_at')->label('When')
                    ->state(fn (FccComplianceEvent $r) => $r->occurred_at?->diffForHumans()),
            ])
            ->paginated(false);
    }
}
