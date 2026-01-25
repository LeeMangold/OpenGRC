<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use App\Models\Control;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'controls';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
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

        // Override the query to show all controls
        return Control::query()->whereIn('id', $controlIds);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $this->modifyQueryUsing($query))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('standard.name')
                    ->label('Standard')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('applicability')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effectiveness')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('standard')
                    ->relationship('standard', 'name')
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Relate to Control')
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => \App\Filament\Resources\ControlResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
