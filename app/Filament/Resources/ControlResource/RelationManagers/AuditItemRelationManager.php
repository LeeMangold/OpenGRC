<?php

namespace App\Filament\Resources\ControlResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'AuditItems';

    // set table name as Audit Results
    public static ?string $title = 'Audit History';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->check() && auth()->user()->can('Read Audits');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['audit']))
            ->emptyStateHeading('No Audits Yet')
            ->emptyStateDescription('When audits are completed for this control, they will appear here.')
            ->recordTitleAttribute('effectiveness')
            ->columns([
                TextColumn::make('audit.title')
                    ->label('Audit Name'),
                TextColumn::make('effectiveness')
                    ->badge(),
                TextColumn::make('audit.updated_at')
                    ->label('Date Assessed')
                    ->badge(),
                TextColumn::make('auditor_notes')
                    ->label('Auditor Notes')
                    ->words(100)
                    ->html(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Audit Item')
                    ->url(fn ($record) => route('filament.app.resources.audit-items.view', $record->id)),
            ]);
    }
}
