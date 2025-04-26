<?php

namespace App\Filament\Resources\StandardResource\RelationManagers;

use App\Enums\Applicability;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'Controls';

    public function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. 3.1.1')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Give the control a unique ID or Code.'),
                Forms\Components\Select::make('applicability')
                    ->default(Applicability::UNKNOWN)
                    ->required()
                    ->enum(Applicability::class)
                    ->options(Applicability::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the relevance of this standard to your organization.')
                    ->native(false),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(1024)
                    ->placeholder('e.g. Limit system access to authorized users, processes acting on behalf of authorized users, and devices (including other systems).')
                    ->hint('Enter the title of the control.')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a succinct description of the control.'),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Describe the control in detail.')
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('discussion')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Provide any explanation, discussion, context, or relevant information to help someone understand the intent of this control.'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->wrap()
                    ->html()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->html()
                    ->wrap()
                    ->limit(300)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add New Control'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hiddenLabel()
                    ->url(fn ($record) => route('filament.app.resources.controls.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
