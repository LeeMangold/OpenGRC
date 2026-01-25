<?php

namespace App\Filament\Resources;

use App\Enums\ResponseStatus;
use App\Filament\Exports\DataRequestExporter;
use App\Filament\Resources\DataRequestResource\Pages\CreateDataRequest;
use App\Filament\Resources\DataRequestResource\Pages\EditDataRequest;
use App\Filament\Resources\DataRequestResource\Pages\ListDataRequests;
use App\Filament\Resources\DataRequestResource\Pages\ViewDataRequest;
use App\Mail\EvidenceRequestMail;
use App\Models\Audit;
use App\Models\DataRequest;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class DataRequestResource extends Resource
{
    protected static ?string $model = DataRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Foundations';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {

        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Assigned To')
                    ->options(User::whereNotNull('name')->pluck('name', 'id')->toArray())
                    ->searchable(),
                Select::make('audit_item_id')
                    ->label('Audit name')
                    ->options(Audit::whereNotNull('title')->pluck('title', 'id')->toArray())
                    ->searchable()
                    ->required(),
                TextInput::make('code')
                    ->label('Request Code')
                    ->maxLength(255)
                    ->helperText('Optional. If left blank, will default to Request-{id} after creation.')
                    ->nullable(),
                Select::make('created_by_id')
                    ->label('Created By')
                    ->options(User::whereNotNull('name')->pluck('name', 'id')->toArray())
                    ->default(auth()->id())
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->label('Response Status')
                    ->options(ResponseStatus::class)
                    ->default(ResponseStatus::PENDING)
                    ->required(),
                RichEditor::make('details')
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('code')
                    ->label('Request Code')
                    ->maxLength(255)
                    ->helperText('Optional. If left blank, will default to Request-{id} after creation.')
                    ->nullable(),
                TextInput::make('code')
                    ->label('Request Code')
                    ->maxLength(255)
                    ->helperText('Optional. If left blank, will default to Request-{id} after creation.')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('audit_item_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Request Code')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(DataRequestExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalFooterActions(fn ($record) => static::getModalFooterActions($record))
                    ->modalSubmitAction(false),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(DataRequestExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataRequests::route('/'),
            'create' => CreateDataRequest::route('/create'),
            'view' => ViewDataRequest::route('/{record}'),
            'edit' => EditDataRequest::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function createResponses(DataRequest $record, ?string $dueDate = null): void
    {
        $record->responses()->create([
            'requester_id' => $record->created_by_id,
            'requestee_id' => $record->assigned_to_id,
            'data_request_id' => $record->id,
            'due_at' => $dueDate,
            'status' => ResponseStatus::PENDING,
        ]);
    }

    public static function getEditForm(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Request Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Placeholder::make('code')
                            ->label('Request Code')
                            ->content(function ($record) {
                                return $record->code;
                            }),
                        Section::make('Data Request Details')
                            ->columnSpanFull()
                            ->schema([
                                Placeholder::make('Requested Information')
                                    ->label('')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        return new HtmlString($record->details ?? '');
                                    }),
                            ]),
                        Section::make('Control Details')
                            ->columnSpanFull()
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Placeholder::make('control')
                                    ->label('Control(s)')
                                    ->content(function ($record) {
                                        // Try many-to-many relationship first
                                        $controls = $record->auditItems->map(function ($item) {
                                            return $item->auditable ? ($item->auditable->code.' - '.$item->auditable->title) : null;
                                        })->filter()->all();

                                        // Fallback to single relationship for backwards compatibility
                                        if (empty($controls) && $record->auditItem?->auditable) {
                                            $controls = [$record->auditItem->auditable->code.' - '.$record->auditItem->auditable->title];
                                        }

                                        return new HtmlString(! empty($controls) ? implode('<br>', $controls) : '-');
                                    })
                                    ->columnSpanFull(),
                                Placeholder::make('control_description')
                                    ->label('Control Description(s)')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        // Try many-to-many relationship first
                                        $descriptions = $record->auditItems->map(function ($item) {
                                            if ($item->auditable) {
                                                return '<strong>'.$item->auditable->code.':</strong> '.$item->auditable->description;
                                            }

                                            return null;
                                        })->filter()->all();

                                        // Fallback to single relationship for backwards compatibility
                                        if (empty($descriptions) && $record->auditItem?->auditable) {
                                            $descriptions = ['<strong>'.$record->auditItem->auditable->code.':</strong> '.$record->auditItem->auditable->description];
                                        }

                                        return new HtmlString(! empty($descriptions) ? implode('<br><br>', $descriptions) : '-');
                                    }),
                            ]),
                        Section::make('Response')
                            ->columnSpanFull()
                            ->columns(3)
                            ->schema([
                                Placeholder::make('requestee_name')
                                    ->label('Assigned To')
                                    ->content(function ($record) {
                                        $response = $record->responses->first();

                                        return $response?->requestee?->name ?? '-';
                                    }),
                                Placeholder::make('response_status')
                                    ->label('Status')
                                    ->content(function ($record) {
                                        $response = $record->responses->first();

                                        return $response?->status?->getLabel() ?? '-';
                                    }),
                                Placeholder::make('due_date')
                                    ->label('Due Date')
                                    ->content(function ($record) {
                                        $response = $record->responses->first();

                                        return $response?->due_at?->format('M d, Y') ?? '-';
                                    }),
                                Placeholder::make('response_text')
                                    ->label('Text Response')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        $response = $record->responses->first();

                                        return new HtmlString($response?->response ?? '<em class="text-gray-400">No response yet</em>');
                                    }),
                                Placeholder::make('attachments')
                                    ->columnSpanFull()
                                    ->label('Attachments')
                                    ->content(function ($record) {
                                        $response = $record->responses->first();

                                        if (! $response || $response->attachments->isEmpty()) {
                                            return new HtmlString('<em class="text-gray-400">No attachments</em>');
                                        }

                                        $output = "<table class='min-w-full divide-y divide-gray-200'>
                                        <thead class='bg-gray-50'>
                                            <tr>
                                                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-auto'>File</th>
                                                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class='bg-white divide-y divide-gray-200'>";

                                        foreach ($response->attachments as $attachment) {
                                            $storage = Storage::disk(config('filesystems.default'));
                                            $downloadUrl = null;

                                            if ($storage->exists($attachment->file_path)) {
                                                $driver = config('filesystems.default');
                                                if (in_array($driver, ['s3', 'minio'])) {
                                                    $downloadUrl = $storage->temporaryUrl($attachment->file_path, now()->addMinutes(5));
                                                } else {
                                                    $downloadUrl = $storage->url($attachment->file_path);
                                                }
                                            }

                                            $output .= "<tr>
                                            <td class='px-6 py-4 whitespace-nowrap w-auto'>";
                                            if ($downloadUrl) {
                                                $output .= "<a href='{$downloadUrl}' class='text-indigo-600 hover:text-indigo-900' target='_blank'>{$attachment->file_name}</a>";
                                            } else {
                                                $output .= "<span class='text-gray-400'>{$attachment->file_name} (not available)</span>";
                                            }
                                            $output .= "</td>
                                            <td class='px-6 py-4 whitespace-normal'>{$attachment->description}</td>
                                        </tr>";
                                        }

                                        $output .= '</tbody></table>';

                                        return new HtmlString($output);
                                    }),
                            ]),
                        Section::make('Comments')
                            ->columnSpanFull()
                            ->collapsible()
                            ->schema([
                                ViewField::make('response_comments')
                                    ->label('')
                                    ->view('filament.forms.components.data-request-comments')
                                    ->dehydrated(false),
                            ]),
                    ]),
            ]);

    }

    public static function getViewFormActions(): array
    {
        return [
            Action::make('reassign')
                ->label('Reassign Request')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->schema([
                    Select::make('new_assignee_id')
                        ->label('Reassign To')
                        ->options(
                            User::query()
                                ->whereNotNull('name')
                                ->whereNull('deleted_at')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->helperText('Select the user to reassign this request to'),
                    Checkbox::make('send_notification')
                        ->label('Send email notification to the new assignee')
                        ->default(true)
                        ->helperText('Uses the evidence request email template'),
                ])
                ->action(function (DataRequest $record, array $data): void {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $newAssignee = User::find($data['new_assignee_id']);

                    if (! $newAssignee) {
                        Notification::make()
                            ->title('Error')
                            ->body('Selected user not found.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Update the response
                    $response->requestee_id = $data['new_assignee_id'];
                    $response->save();

                    // Send email notification if checkbox is checked
                    if ($data['send_notification'] && $newAssignee->email) {
                        try {
                            Mail::send(new EvidenceRequestMail($newAssignee->email, $newAssignee->name));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Warning')
                                ->body('Request reassigned but email notification failed to send.')
                                ->warning()
                                ->send();

                            return;
                        }
                    }

                    Notification::make()
                        ->title('Success')
                        ->body("Request reassigned to {$newAssignee->name}".($data['send_notification'] ? ' and notification sent.' : '.'))
                        ->success()
                        ->send();
                })
                ->modalWidth('md'),
        ];
    }

    public static function getModalFooterActions(DataRequest $record): array
    {
        $actions = [];

        if ($record->responses()->exists()) {
            // Accept button
            $actions[] = Action::make('set_accepted')
                ->label('Accept')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (DataRequest $record) {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $response->status = ResponseStatus::ACCEPTED;
                    $response->save();

                    Notification::make()
                        ->title('Success')
                        ->body('Request accepted.')
                        ->success()
                        ->send();
                })
                ->after(fn (DataRequest $record) => redirect("/app/audits/{$record->audit_id}?activeRelationManager=1"));

            // Reject button
            $actions[] = Action::make('set_rejected')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (DataRequest $record) {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $response->status = ResponseStatus::REJECTED;
                    $response->save();

                    Notification::make()
                        ->title('Success')
                        ->body('Request rejected.')
                        ->success()
                        ->send();
                })
                ->after(fn (DataRequest $record) => redirect("/app/audits/{$record->audit_id}?activeRelationManager=1"));

            // Reassign button
            $actions[] = Action::make('reassign')
                ->label('Reassign')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->schema([
                    Select::make('new_assignee_id')
                        ->label('Reassign To')
                        ->options(
                            User::query()
                                ->whereNotNull('name')
                                ->whereNull('deleted_at')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->helperText('Select the user to reassign this request to'),
                    Checkbox::make('send_notification')
                        ->label('Send email notification to the new assignee')
                        ->default(true)
                        ->helperText('Uses the evidence request email template'),
                ])
                ->action(function (DataRequest $record, array $data) {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $newAssignee = User::find($data['new_assignee_id']);

                    if (! $newAssignee) {
                        Notification::make()
                            ->title('Error')
                            ->body('Selected user not found.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Update the response and set status to Pending
                    $response->requestee_id = $data['new_assignee_id'];
                    $response->status = ResponseStatus::PENDING;
                    $response->save();

                    // Send email notification if checkbox is checked
                    if ($data['send_notification'] && $newAssignee->email) {
                        try {
                            Mail::send(new EvidenceRequestMail($newAssignee->email, $newAssignee->name));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Warning')
                                ->body('Request reassigned but email notification failed to send.')
                                ->warning()
                                ->send();

                            return;
                        }
                    }

                    Notification::make()
                        ->title('Success')
                        ->body("Request reassigned to {$newAssignee->name}".($data['send_notification'] ? ' and notification sent.' : '.').'.')
                        ->success()
                        ->send();
                })
                ->after(fn (DataRequest $record) => redirect("/app/audits/{$record->audit_id}?activeRelationManager=1"));

            // Change Due Date button (only for Audit Manager)
            $audit = $record->audit;
            if ($audit && $audit->manager_id === auth()->id()) {
                $actions[] = Action::make('change_due_date')
                    ->label('Change Due Date')
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->schema([
                        DatePicker::make('new_due_date')
                            ->label('New Due Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->helperText('Select the new due date for this data request'),
                    ])
                    ->action(function (DataRequest $record, array $data) {
                        $response = $record->responses()->first();

                        if (! $response) {
                            Notification::make()
                                ->title('Error')
                                ->body('No response found for this data request.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Update the due date
                        $response->due_at = $data['new_due_date'];
                        $response->save();

                        Notification::make()
                            ->title('Success')
                            ->body('Due date updated successfully.')
                            ->success()
                            ->send();
                    })
                    ->after(fn (DataRequest $record) => redirect("/app/audits/{$record->audit_id}?activeRelationManager=1"));
            }
        }

        return $actions;
    }

    public static function getPageFooterActions(DataRequest $record): array
    {
        $actions = [];

        if ($record->responses()->exists()) {
            // Accept button
            $actions[] = Action::make('set_accepted')
                ->label('Accept')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function (DataRequest $record) {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $response->status = ResponseStatus::ACCEPTED;
                    $response->save();

                    Notification::make()
                        ->title('Success')
                        ->body('Request accepted.')
                        ->success()
                        ->send();
                })
                ->successRedirectUrl(fn (DataRequest $record) => "/app/audits/{$record->audit_id}?activeRelationManager=1");

            // Reject button
            $actions[] = Action::make('set_rejected')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (DataRequest $record) {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $response->status = ResponseStatus::REJECTED;
                    $response->save();

                    Notification::make()
                        ->title('Success')
                        ->body('Request rejected.')
                        ->success()
                        ->send();
                })
                ->successRedirectUrl(fn (DataRequest $record) => "/app/audits/{$record->audit_id}?activeRelationManager=1");

            // Reassign button
            $actions[] = Action::make('reassign')
                ->label('Reassign')
                ->icon('heroicon-o-user-group')
                ->color('gray')
                ->schema([
                    Select::make('new_assignee_id')
                        ->label('Reassign To')
                        ->options(
                            User::query()
                                ->whereNotNull('name')
                                ->whereNull('deleted_at')
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->searchable()
                        ->required()
                        ->helperText('Select the user to reassign this request to'),
                    Checkbox::make('send_notification')
                        ->label('Send email notification to the new assignee')
                        ->default(true)
                        ->helperText('Uses the evidence request email template'),
                ])
                ->action(function (DataRequest $record, array $data) {
                    $response = $record->responses()->first();

                    if (! $response) {
                        Notification::make()
                            ->title('Error')
                            ->body('No response found for this data request.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $newAssignee = User::find($data['new_assignee_id']);

                    if (! $newAssignee) {
                        Notification::make()
                            ->title('Error')
                            ->body('Selected user not found.')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Update the response and set status to Pending
                    $response->requestee_id = $data['new_assignee_id'];
                    $response->status = ResponseStatus::PENDING;
                    $response->save();

                    // Send email notification if checkbox is checked
                    if ($data['send_notification'] && $newAssignee->email) {
                        try {
                            Mail::send(new EvidenceRequestMail($newAssignee->email, $newAssignee->name));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Warning')
                                ->body('Request reassigned but email notification failed to send.')
                                ->warning()
                                ->send();

                            return;
                        }
                    }

                    Notification::make()
                        ->title('Success')
                        ->body("Request reassigned to {$newAssignee->name}".($data['send_notification'] ? ' and notification sent.' : '.').'.')
                        ->success()
                        ->send();
                })
                ->successRedirectUrl(fn (DataRequest $record) => "/app/audits/{$record->audit_id}?activeRelationManager=1");

            // Change Due Date button (only for Audit Manager)
            $audit = $record->audit;
            if ($audit && $audit->manager_id === auth()->id()) {
                $actions[] = Action::make('change_due_date')
                    ->label('Change Due Date')
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->schema([
                        DatePicker::make('new_due_date')
                            ->label('New Due Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->helperText('Select the new due date for this data request'),
                    ])
                    ->action(function (DataRequest $record, array $data) {
                        $response = $record->responses()->first();

                        if (! $response) {
                            Notification::make()
                                ->title('Error')
                                ->body('No response found for this data request.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Update the due date
                        $response->due_at = $data['new_due_date'];
                        $response->save();

                        Notification::make()
                            ->title('Success')
                            ->body('Due date updated successfully.')
                            ->success()
                            ->send();
                    })
                    ->successRedirectUrl(fn (DataRequest $record) => "/app/audits/{$record->audit_id}?activeRelationManager=1");
            }
        }

        return $actions;
    }
}
