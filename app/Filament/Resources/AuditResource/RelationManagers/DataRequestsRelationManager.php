<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource;
use App\Models\DataRequest;
use App\Models\Audit;
use Symfony\Component\Process\Process;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\QueueController;

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
                TextColumn::make('code')
                    ->toggleable()
                    ->label('Request Code'),
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
                Tables\Filters\SelectFilter::make('code')
                    ->options(DataRequest::pluck('code', 'code')->toArray())
                    ->label('Request Code'),
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
                        return $this->getOwnerRecord()->manager_id != auth()->id();
                    })
                    ->hidden(function () {
                        return $this->getOwnerRecord()->manager_id != auth()->id();
                    })
                    ->action(function () {
                        $audit = $this->getOwnerRecord();

                        return redirect()->route('filament.app.resources.audits.import-irl', $audit);
                    }),
                Tables\Actions\Action::make('ExportAuditEvidence')
                    ->label('Export All Evidence')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->requiresConfirmation()
                    ->modalHeading('Export All Evidence')
                    ->modalDescription('This will generate a PDF for each audit item and zip them for download. You will be notified when the export is ready.')
                    ->action(function ($livewire) {
                        $audit = $this->getOwnerRecord();
                        \App\Jobs\ExportAuditEvidenceJob::dispatch($audit->id);
                        
                        // Ensure queue worker is running
                        $queueController = new QueueController();
                        $wasAlreadyRunning = $queueController->ensureQueueWorkerRunning();
                        
                        $body = $wasAlreadyRunning 
                            ? 'The export job has been added to the queue. You will be able to download the ZIP in the Attachments section.'
                            : 'The export job has been queued and a queue worker has been started. You will be able to download the ZIP in the Attachments section.';
                        
                        return Notification::make()
                            ->title('Export Started')
                            ->body($body)
                            ->success()
                            ->send();
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
