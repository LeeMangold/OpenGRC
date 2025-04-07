<?php

namespace App\Filament\Resources\RiskResource\RelationManagers;

use App\Filament\Resources\ImplementationResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    public function form(Form $form): Form
    {
        return ImplementationResource::getForm($form);
    }

    public function table(Table $table): Table
    {
        $table = ImplementationResource::getTable($table);
        $table->actions([
            Tables\Actions\ViewAction::make()->hidden(),
        ]);

        return $table;
    }
}
