<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use App\Filament\Resources\ImplementationResource;
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
        $table->recordActions([
            ViewAction::make()->hidden(),
        ]);

        return $table;
    }
}
