<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\ResponseStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource;
use App\Http\Controllers\QueueController;
use App\Jobs\ExportAuditEvidenceJob;
use App\Models\DataRequest;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DataRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'DataRequest';

    protected $listeners = ['refreshComponent' => '$refresh'];

    /**
     * Check if an export job is pending or running in the queue for the given audit.
     */
    protected function isExportInProgress(int $auditId): bool
    {
        // Check the jobs table for pending/reserved ExportAuditEvidenceJob for this audit
        // PHP serialization uses null bytes for protected properties: \0*\0propertyName
        // We need to search for the pattern with the actual null bytes
        $pattern = '%'.chr(0).'*'.chr(0).'auditId";i:'.$auditId.';%';

        return DB::table('jobs')
            ->where('payload', 'like', '%ExportAuditEvidenceJob%')
            ->where('payload', 'like', $pattern)
            ->exists();
    }

    public function form(Schema $schema): Schema
    {
        return DataRequestResource::getEditForm($schema);
    }

    /**
     * @throws Exception
     */
    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('id')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->label('ID'),
                TextColumn::make('auditItems')
                    ->label('Audit Item(s)')
                    ->wrap()
                    ->state(function (DataRequest $record) {
                        // Try the many-to-many relationship first (for new data requests)
                        $codes = $record->auditItems->pluck('auditable.code')->filter()->all();

                        // Fallback to single relationship for backwards compatibility
                        if (empty($codes) && $record->auditItem?->auditable) {
                            $codes = [$record->auditItem->auditable->code];
                        }

                        return ! empty($codes) ? implode(', ', $codes) : '-';
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('auditItems.auditable', function ($q) use ($search) {
                            $q->where('code', 'like', "%{$search}%");
                        })->orWhereHas('auditItem.auditable', function ($q) use ($search) {
                            $q->where('code', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('code')
                    ->searchable()
                    ->toggleable()
                    ->sortable()
                    ->label('Request Code'),
                TextColumn::make('details')
                    ->label('Request Details')
                    ->searchable()
                    ->html()
                    ->wrap(),
                TextColumn::make('responses.status')
                    ->label('Responses')
                    ->sortable()
                    ->badge(),
                TextColumn::make('responses.requestee.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->default('-'),
                TextColumn::make('responses.due_at')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ResponseStatus::class)
                    ->label('Status')
                    ->query(function ($query, $state) {
                        if ($state['value'] ?? null) {
                            return $query->whereHas('responses', function ($query) use ($state) {
                                $query->where('status', $state['value']);
                            });
                        }
                    }),
                SelectFilter::make('assigned_to')
                    ->options(function () {
                        return User::withTrashed()
                            ->whereHas('todos')
                            ->get()
                            ->mapWithKeys(fn (User $user) => [
                                $user->id => $user->trashed() ? "{$user->name} (Deactivated)" : $user->name,
                            ])
                            ->toArray();
                    })
                    ->label('Assigned To')
                    ->query(function ($query, $state) {
                        if ($state['value'] ?? null) {
                            return $query->whereHas('responses', function ($query) use ($state) {
                                $query->where('requestee_id', $state['value']);
                            });
                        }
                    }),
                SelectFilter::make('code')
                    ->options(DataRequest::whereNotNull('code')->pluck('code', 'code')->toArray())
                    ->label('Request Code'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create Data Request')
                    ->modalHeading('Create New Data Request')
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->schema([
                        Select::make('audit_items')
                            ->label('Audit Item(s)')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                $audit = $this->getOwnerRecord();

                                return $audit->auditItems()
                                    ->with('auditable')
                                    ->get()
                                    ->mapWithKeys(function ($item) {
                                        $label = $item->auditable
                                            ? $item->auditable->code.' - '.$item->auditable->title
                                            : 'Item #'.$item->id;

                                        return [$item->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->helperText('Select one or more audit items for this data request'),
                        TextInput::make('code')
                            ->label('Request Code')
                            ->maxLength(255)
                            ->helperText('Optional. If left blank, will default to Request-{id} after creation.')
                            ->nullable(),
                        RichEditor::make('details')
                            ->label('Request Details')
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles',
                            ])
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Describe what information or evidence is being requested'),
                        Select::make('assigned_to_id')
                            ->label('Assign To')
                            ->options(User::activeOptions())
                            ->searchable()
                            ->required()
                            ->helperText('User responsible for responding to this request'),
                        DatePicker::make('due_at')
                            ->label('Due Date')
                            ->required()
                            ->helperText('When should this request be completed?'),
                    ])
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by_id'] = auth()->id();
                        $data['audit_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    })
                    ->using(function (array $data, string $model): DataRequest {
                        // Extract audit items and due date before creating the data request
                        $auditItems = $data['audit_items'];
                        $dueAt = $data['due_at'];
                        unset($data['audit_items'], $data['due_at']);

                        // Create the data request
                        $dataRequest = $model::create($data);

                        // Set the code if not provided
                        if (empty($dataRequest->code)) {
                            $dataRequest->code = 'Request-'.$dataRequest->id;
                            $dataRequest->save();
                        }

                        // Attach the audit items
                        $dataRequest->auditItems()->attach($auditItems);

                        // Create the response
                        DataRequestResource::createResponses($dataRequest, $dueAt);

                        return $dataRequest;
                    })
                    ->successNotificationTitle('Data Request Created')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Data Request Created')
                            ->body('The data request has been created and assigned successfully.')
                    ),
                Action::make('import_irl')
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
                Action::make('ExportAuditEvidence')
                    ->label(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = $this->isExportInProgress($audit->id);

                        return $isExporting ? 'Export In Progress...' : 'Export All Evidence';
                    })
                    ->icon(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = $this->isExportInProgress($audit->id);

                        // Use custom spinner icon when exporting (registered in AppServiceProvider)
                        return $isExporting ? 'grc-spinner' : 'heroicon-m-arrow-down-tray';
                    })
                    ->disabled(function () {
                        $audit = $this->getOwnerRecord();

                        return $this->isExportInProgress($audit->id);
                    })
                    ->color(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = $this->isExportInProgress($audit->id);

                        return $isExporting ? 'warning' : 'primary';
                    })
                    ->extraAttributes(function () {
                        $audit = $this->getOwnerRecord();
                        $isExporting = $this->isExportInProgress($audit->id);

                        return $isExporting ? ['class' => 'animate-pulse'] : [];
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Export All Evidence')
                    ->modalDescription('This will generate a PDF for each audit item and zip them for download. You will be notified when the export is ready.')
                    ->action(function ($livewire) {
                        $audit = $this->getOwnerRecord();

                        // Check queue before dispatching
                        if ($this->isExportInProgress($audit->id)) {
                            return Notification::make()
                                ->title('Export Already In Progress')
                                ->body('An export is already running for this audit. Please wait for it to complete.')
                                ->warning()
                                ->send();
                        }

                        ExportAuditEvidenceJob::dispatch($audit->id, auth()->id());

                        // Ensure queue worker is running
                        if (env('QUEUE_AUTO_START') == true) {
                            $queueController = new QueueController;
                            $wasAlreadyRunning = $queueController->ensureQueueWorkerRunning();
                        }

                        $body = 'Your evidence export has started and is being processed in the background.';

                        Notification::make()
                            ->title('Export Started')
                            ->body($body)
                            ->success()
                            ->send();
                    }),
            ])

            ->recordActions([
                EditAction::make()
                    ->modalHeading('View Data Request')
                    ->modalFooterActions(fn ($record) => DataRequestResource::getModalFooterActions($record))
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    }),
                DeleteAction::make()
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->visible(function () {
                        return $this->getOwnerRecord()->status == WorkflowStatus::INPROGRESS;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulk_assign_requestee')
                        ->label('Bulk Assign Requestee')
                        ->icon('heroicon-o-user-plus')
                        ->color('primary')
                        ->form([
                            Select::make('requestee_id')
                                ->label('Assign to User')
                                ->options(User::activeOptions())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            $requesteeId = $data['requestee_id'];
                            $updatedCount = 0;

                            foreach ($records as $dataRequest) {
                                $response = $dataRequest->responses->first();
                                if ($response) {
                                    $response->update(['requestee_id' => $requesteeId]);
                                    $updatedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk Assignment Complete')
                                ->body("Successfully assigned {$updatedCount} data request responses.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Assign Requestee')
                        ->modalDescription('This will assign the selected user as the requestee for the first response of each selected data request.')
                        ->disabled(function () {
                            return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                        }),
                    BulkAction::make('bulk_update_status')
                        ->label('Bulk Update Status')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Select::make('status')
                                ->label('Status')
                                ->options(ResponseStatus::class)
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            $status = $data['status'];
                            $updatedCount = 0;

                            foreach ($records as $dataRequest) {
                                $response = $dataRequest->responses->first();
                                if ($response) {
                                    $response->update(['status' => $status]);
                                    $updatedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk Status Update Complete')
                                ->body("Successfully updated status for {$updatedCount} data request responses.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Update Status')
                        ->modalDescription('This will update the status for the first response of each selected data request.')
                        ->disabled(function () {
                            return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                        }),
                    BulkAction::make('bulk_change_due_date')
                        ->label('Bulk Change Due Date')
                        ->icon('heroicon-o-calendar')
                        ->color('warning')
                        ->form([
                            DatePicker::make('new_due_date')
                                ->label('New Due Date')
                                ->required()
                                ->native(false)
                                ->displayFormat('M d, Y')
                                ->helperText('This will update the due date for all selected data requests where you are the Audit Manager'),
                        ])
                        ->action(function (array $data, Collection $records) {
                            $userId = auth()->id();
                            $audit = $this->getOwnerRecord();
                            $updated = 0;
                            $skipped = 0;

                            foreach ($records as $dataRequest) {
                                // Check if user is the Audit Manager for this audit
                                if ($audit->manager_id !== $userId) {
                                    $skipped++;

                                    continue;
                                }

                                $response = $dataRequest->responses->first();
                                if (! $response) {
                                    $skipped++;

                                    continue;
                                }

                                // Update the due date
                                $response->update(['due_at' => $data['new_due_date']]);
                                $updated++;
                            }

                            // Show notification with results
                            $message = "Updated {$updated} request(s).";
                            if ($skipped > 0) {
                                $message .= " Skipped {$skipped} request(s) (not authorized or no response).";
                            }

                            Notification::make()
                                ->title($updated > 0 ? 'Bulk Due Date Update Complete' : 'Warning')
                                ->body($message)
                                ->color($updated > 0 ? 'success' : 'warning')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Change Due Date')
                        ->modalDescription('This will update the due date for the first response of each selected data request. Only available to the Audit Manager.')
                        ->visible(function () {
                            return $this->getOwnerRecord()->manager_id === auth()->id();
                        })
                        ->disabled(function () {
                            return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                        }),
                    BulkAction::make('export_selected_evidence')
                        ->label('Export Selected Evidence')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Export Selected Evidence')
                        ->modalDescription(function (Collection $records): string {
                            $count = $records->count();

                            return "This will generate a PDF for each of the {$count} selected data request(s) and zip them for download. You will be notified when the export is ready.";
                        })
                        ->action(function (Collection $records): void {
                            $audit = $this->getOwnerRecord();
                            $dataRequestIds = $records->pluck('id')->toArray();

                            ExportAuditEvidenceJob::dispatch(
                                $audit->id,
                                auth()->id(),
                                $dataRequestIds
                            );

                            // Ensure queue worker is running
                            if (env('QUEUE_AUTO_START') == true) {
                                $queueController = new QueueController;
                                $queueController->ensureQueueWorkerRunning();
                            }

                            Notification::make()
                                ->title('Export Started')
                                ->body('Your partial evidence export has started and is being processed in the background.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
