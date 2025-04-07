<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'auditItems';

    // set table name as Audit Results
    public static ?string $title = 'Audit History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('effectiveness')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('effectiveness')
            ->columns([
                Tables\Columns\TextColumn::make('audit.title')
                    ->label('Audit Name'),
                Tables\Columns\TextColumn::make('effectiveness'),
                Tables\Columns\TextColumn::make('audit.updated_at')
                    ->label('Date Assessed'),
                Tables\Columns\TextColumn::make('auditor_notes')
                    ->label('Auditor Notes')
                    ->words(100)
                    ->html(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
