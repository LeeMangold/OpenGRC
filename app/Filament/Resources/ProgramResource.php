<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramResource\Pages;
use App\Filament\Resources\ProgramResource\RelationManagers;
use App\Models\Program;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('navigation.resources.program');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.foundations');
    }

    public static function getModelLabel(): string
    {
        return __('programs.labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('programs.labels.plural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('programs.form.name'))
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('programs.form.description'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('program_manager_id')
                    ->label(__('programs.form.program_manager'))
                    ->relationship('programManager', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('scope_status')
                    ->label(__('programs.form.scope_status'))
                    ->options([
                        'In Scope' => __('programs.scope_status.in_scope'),
                        'Out of Scope' => __('programs.scope_status.out_of_scope'),
                        'Pending Review' => __('programs.scope_status.pending_review'),
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(new class implements \Illuminate\Contracts\Support\Htmlable
            {
                public function toHtml()
                {
                    return "<div class='fi-section-content p-6'>" . __('programs.description') . "</div>";
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('programs.table.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('programManager.name')
                    ->label(__('programs.table.program_manager'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_audit_date')
                    ->label(__('programs.table.last_audit_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scope_status')
                    ->label(__('programs.table.scope_status'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('programs.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('programs.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StandardsRelationManager::class,
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\RisksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrograms::route('/'),
            'create' => Pages\CreateProgram::route('/create'),
            'edit' => Pages\EditProgram::route('/{record}/edit'),
        ];
    }
}
