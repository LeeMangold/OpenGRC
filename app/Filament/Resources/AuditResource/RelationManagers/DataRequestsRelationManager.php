<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource;
use App\Models\DataRequest;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DataRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'DataRequest';

    public function form(Form $form): Form
    {
        return DataRequestResource::getEditForm($form);
    }

    /**
     * @throws \Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->label('ID'),
                TextColumn::make('auditItem.auditable.code')
                    ->label('Audit Item'),
                TextColumn::make('details')
                    ->label('Request Details')
                    ->wrap(),
                TextColumn::make('responses.status')
                    ->label('Responses')
                    ->badge(),
                TextColumn::make('assignedTo.name'),
                TextColumn::make('responses')
                    ->label('Due Date')
                    ->date()
                    ->state(function (DataRequest $record) {
                        return $record->responses->sortByDesc('due_at')->first()?->due_at;
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(WorkflowStatus::class)
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('assigned_to_id')
                    ->options(DataRequest::pluck('assigned_to_id', 'assigned_to_id')->toArray())
                    ->label('Assigned To'),

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->hidden()
                    ->after(function (DataRequest $record, Tables\Actions\Action $action) {
                        DataRequestResource::createResponses($record);
                    }),
                Tables\Actions\Action::make('import_irl')
                    ->label('Import IRL')
                    ->color('primary')
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS || $this->getOwnerRecord()->manager_id != auth()->id();
                    })
                    ->hidden(function () {
                        return $this->getOwnerRecord()->manager_id != auth()->id();
                    })
                    ->action(function () {
                        $audit = $this->getOwnerRecord();

                        return redirect()->route('filament.app.resources.audits.import-irl', $audit);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('View Data Request')
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    }),
                Tables\Actions\DeleteAction::make()
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->visible(function () {
                        return $this->getOwnerRecord()->status == WorkflowStatus::INPROGRESS;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
