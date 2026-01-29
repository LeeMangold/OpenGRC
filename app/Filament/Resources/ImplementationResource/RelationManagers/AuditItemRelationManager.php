<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditItemRelationManager extends RelationManager
{
    protected static string $relationship = 'auditItems';

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
            ->recordTitleAttribute('effectiveness')
            ->columns([
                TextColumn::make('audit.title')
                    ->label('Audit Name'),
                TextColumn::make('effectiveness'),
                TextColumn::make('audit.updated_at')
                    ->label('Date Assessed'),
                TextColumn::make('auditor_notes')
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
            ->recordActions([
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
