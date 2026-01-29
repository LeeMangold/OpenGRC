<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use App\Filament\Resources\AuditResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['manager']))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('status')
                    ->label(__('audit.table.columns.status'))
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('manager.name')
                    ->label(__('audit.table.columns.manager'))
                    ->default('Unassigned')
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label(__('audit.table.columns.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('audit.table.columns.end_date'))
                    ->date()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create New Audit')
                    ->url(fn (): string => AuditResource::getUrl('create', ['default_program_id' => $this->getOwnerRecord()->getKey()])
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => AuditResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
