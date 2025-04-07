<?php

namespace App\Filament\Resources\ControlResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'AuditItems';

    // set table name as Audit Results
    public static ?string $title = 'Audit History';

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Audits Yet')
            ->emptyStateDescription('When audits are completed for this control, they will appear here.')
            ->recordTitleAttribute('effectiveness')
            ->columns([
                Tables\Columns\TextColumn::make('audit.title')
                    ->label('Audit Name'),
                Tables\Columns\TextColumn::make('effectiveness')
                    ->badge(),
                Tables\Columns\TextColumn::make('audit.updated_at')
                    ->label('Date Assessed')
                    ->badge(),
                Tables\Columns\TextColumn::make('auditor_notes')
                    ->label('Auditor Notes')
                    ->words(100)
                    ->html(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Audit Item')
                    ->url(fn ($record) => route('filament.app.resources.audit-items.view', $record->id)),
            ]);
    }
}
