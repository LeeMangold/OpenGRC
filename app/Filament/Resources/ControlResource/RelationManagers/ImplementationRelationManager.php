<?php

namespace App\Filament\Resources\ControlResource\RelationManagers;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImplementationRelationManager extends RelationManager
{
    protected static string $relationship = 'Implementations';

    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. ACME-123')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Give the implementation a unique ID or Code.'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->enum(ImplementationStatus::class)
                    ->options(ImplementationStatus::class)
                    ->default(ImplementationStatus::UNKNOWN)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select an implementation status. This will also be assessed in audits.')
                    ->native(false),
                Forms\Components\TextInput::make('title')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Quarterly Access Reviews')
                    ->hint('Enter the title of the implementation.')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a detailed description of this implementation in sufficient detail to both implement and test.'),
                Forms\Components\RichEditor::make('details')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->label('Implementation Details')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a detailed description of this implementation in sufficient detail to both implement and test.'),
                Forms\Components\RichEditor::make('notes')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->label('Internal Notes')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'These notes are for internal use only and will not be shared with auditors.')
                    ->maxLength(4096),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('details')
            ->columns([
                Tables\Columns\TextColumn::make('details')
                    ->html()
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effectiveness')
                    ->getStateUsing(fn ($record) => $record->getEffectiveness())
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_assessed')
                    ->label('Last Audit')
                    ->getStateUsing(fn ($record) => $record->getEffectivenessDate() ? $record->getEffectivenessDate() : 'Not yet audited')
                    ->sortable(true)
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ImplementationStatus::class),
                SelectFilter::make('effectiveness')->options(Effectiveness::class),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->label('Add Existing Implementation')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['id', 'code', 'title']); // Select only necessary columns
                    })
                    ->recordTitle(function ($record) {
                        // Concatenate code and title for the option label
                        return "({$record->code}) {$record->title}";
                    })
                    ->recordSelectSearchColumns(['code', 'title']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //                    ->url(fn ($record) => route('filament.app.resources.implementations.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\DetachBulkAction::make()->label('Detach from this Control'),
                ]),
            ]);
    }
}
