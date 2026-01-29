<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use App\Filament\Resources\ControlResource;
use App\Models\Control;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'controls';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    protected function modifyQueryUsing(Builder $query): Builder
    {
        $program = $this->getOwnerRecord();

        // Get all control IDs from the program (direct + from standards)
        $allControls = $program->getAllControls();
        $controlIds = $allControls->pluck('id')->toArray();

        // Override the query to show all controls with eager loading
        return Control::query()
            ->whereIn('id', $controlIds)
            ->with(['standard', 'latestCompletedAudit']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $this->modifyQueryUsing($query))
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('standard.name')
                    ->label('Standard')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('applicability')
                    ->badge()
                    ->sortable(),
                TextColumn::make('effectiveness')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('standard')
                    ->relationship('standard', 'name')
                    ->preload(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Relate to Control')
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => ControlResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
