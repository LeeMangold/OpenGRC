<?php

namespace App\Filament\Resources\ControlResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'policies';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['status', 'department']))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->wrap(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'In Review' => 'info',
                        'Awaiting Feedback' => 'warning',
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Archived' => 'gray',
                        'Superseded', 'Retired' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Relate to Policy')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['policies.id', 'policies.code', 'policies.name']);
                    })
                    ->recordTitle(function ($record) {
                        return strip_tags("({$record->code}) {$record->name}");
                    })
                    ->recordSelectSearchColumns(['code', 'name']),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.policies.view', $record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Detach from Control'),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
