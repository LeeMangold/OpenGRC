<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class AuditListWidget extends BaseWidget
{
    protected int|string|array $columnSpan = '2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()->latest('updated_at')->take(5)
            )
            ->emptyStateHeading(new HtmlString('No Audits to Show'))
            ->emptyStateDescription('You have not created any audits yet. Create an Audit to get started.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->url(fn (Audit $audit) => route('filament.app.resources.audits.view', $audit)),
                Tables\Columns\TextColumn::make('manager_id')
                    ->label('Manager')
                    ->state(fn (Audit $audit) => $audit->manager->name),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->wrap(),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('View All Audits')
                    ->url(route('filament.app.resources.audits.index'))
                    ->color('primary')
                    ->size('xs'),
            ]);
    }
}
