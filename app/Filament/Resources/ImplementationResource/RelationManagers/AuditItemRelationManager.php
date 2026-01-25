<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'auditItems';

    // set table name as Audit Results
    public static ?string $title = 'Audit History';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return auth()->check() && auth()->user()->can('Read Audits');
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
