<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use App\Filament\Resources\ImplementationResource;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    public function form(Schema $schema): Schema
    {
        return ImplementationResource::getForm($schema);
    }

    public function table(Table $table): Table
    {
        $table = ImplementationResource::getTable($table);
        $table->modifyQueryUsing(fn (Builder $query) => $query->with(['latestCompletedAudit', 'implementationOwner' => fn ($q) => $q->withTrashed()]));
        $table->headerActions([
            CreateAction::make()
                ->label('New Implementation'),
            AttachAction::make()
                ->label('Add Existing Implementation')
                ->preloadRecordSelect()
                ->recordSelectOptionsQuery(function (Builder $query) {
                    $query->select(['implementations.id', 'code', 'title']);
                })
                ->recordTitle(function ($record) {
                    return strip_tags("({$record->code}) {$record->title}");
                })
                ->recordSelectSearchColumns(['code', 'title']),
        ]);
        $table->recordActions([
            ViewAction::make()->hidden(),
            EditAction::make()
                ->modalHeading('Edit Implementation'),
        ]);

        return $table;
    }
}
