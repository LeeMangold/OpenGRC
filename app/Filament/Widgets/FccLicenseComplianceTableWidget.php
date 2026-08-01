<?php

namespace App\Filament\Widgets;

use App\Models\FccLicense;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FccLicenseComplianceTableWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'License Compliance';

    public function table(Table $table): Table
    {
        return $table
            ->query(FccLicense::query()->orderBy('call_sign'))
            ->description('Real-time compliance status for all licensed operations')
            ->columns([
                TextColumn::make('call_sign')->label('Call Sign')->weight('bold')->searchable()->sortable(),
                TextColumn::make('licensee')->searchable()->limit(28),
                TextColumn::make('service')->badge()->color('warning'),
                TextColumn::make('status')->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'expiring_soon', 'at_risk' => 'warning',
                        'non_compliant' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str($state)->headline()),
                TextColumn::make('expiration_date')->date('M d, Y')->label('Expiration')->sortable(),
                TextColumn::make('compliance_score')
                    ->label('Compliance Score')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => $state >= 95 ? 'success' : ($state >= 80 ? 'warning' : 'danger')),
            ])
            ->paginated([10]);
    }
}
