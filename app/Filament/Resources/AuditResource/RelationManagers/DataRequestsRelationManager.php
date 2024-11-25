<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Enums\ResponseStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\DataRequestResource;
use App\Models\AuditItem;
use App\Models\DataRequest;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

class DataRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'DataRequest';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Request')
                    ->columns(2)
                    ->schema([
                        Hidden::make('created_by_id')
                            ->label('Created By')
                            ->default(auth()->id()),
                        Select::make('assigned_to_id')
                            ->label('Assigned To')
                            ->relationship('assignedTo', 'name')
                            ->default($this->getOwnerRecord()->manager_id)
                            ->required(),
                        Select::make('audit_item_id')
                            ->label('Audit Item')
                            ->options(function () {
                                return AuditItem::with('control')
                                    ->where('audit_id', $this->getOwnerRecord()->id)
                                    ->get()
                                    ->mapWithKeys(function ($auditItem) {
                                        // Concatenate 'code' and 'title'
                                        return [$auditItem->id => $auditItem->auditable->code . ' - ' . $auditItem->auditable->title];
                                    });
                            })
                            ->required(),
                        Select::make('audit_id')
                            ->label('Audit')
                            ->relationship('audit', 'title')
                            ->default($this->getOwnerRecord()->id)
                            ->disabled()
                            ->hidden()
                            ->required(),
                        Select::make('status')
                            ->label('Status')
                            ->options(WorkflowStatus::class)
                            ->default(WorkflowStatus::INPROGRESS)
                            ->disabled()
                            ->hidden()
                            ->required(),
                        Textarea::make('details')
                            ->label('Detailed Request')
                            ->columnSpanFull()
                            ->required(),

                    ])->hidden(function ($get, $record) {
                        if (is_null($record)) {
                            return false;
                        } else if ($record->assigned_to_id != auth()->id()) {
                            return false;
                        } else {
                            return true;
                        }
                    }),

                Section::make('Auditor Request')
                    ->hidden(function ($get, $record) {
                        if (is_null($record)) {
                            return true;
                        } else if ($record->assigned_to_id === auth()->id()) {
                            return false;
                        } else {
                            return true;
                        }
                    })
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('details')
                            ->label('Auditor Details')
                            ->columnSpanFull()
                            ->content(function ($record) {
                                return $record->details;
                            }),
                        Placeholder::make('control')
                            ->label('Control')
                            ->columnSpanFull()
                            ->content(function ($record) {
                                return $record->auditItem->auditable->code . ' - ' . $record->auditItem->auditable->title;
                            }),
                        Placeholder::make('control_title')
                            ->label('Control Title')
                            ->columnSpanFull()
                            ->content(function ($record) {
                                return new HtmlString($record->auditItem->auditable->description);
                            }),
                        Repeater::make('responses')
                            ->label('Responses')
                            ->relationship('responses')
                            ->addable(false)
                            ->columns(2)
                            ->deletable(false)
                            ->schema([
                                Select::make('requestee_id')
                                    ->label('Assigned To')
                                    ->relationship('requestee', 'name')
                                    ->required(),
                                ToggleButtons::make('status')
                                    ->label('Status')
                                    ->options(ResponseStatus::class)
                                    ->default(ResponseStatus::PENDING)
                                    ->grouped()
                                    ->required(),
                                Placeholder::make('response')
                                    ->label('Text Response')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                        return new HtmlString($record ? $record->response : '');
                                    })
                                    ->label('Response'),
                                Placeholder::make('attachments')
                                    ->columnSpanFull()
                                    ->content(function ($record) {
                                    $output = "<table class='min-w-full divide-y divide-gray-200'>
                                        <thead class='bg-gray-50'>
                                            <tr>
                                                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-auto'>File</th>
                                                <th class='px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider'>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class='bg-white divide-y divide-gray-200'>";
                                    if ($record) {
                                        foreach (json_decode($record->attachments, true) as $attachment) {
                                            $signedUrl = URL::signedRoute('priv-storage', ['filepath' => $attachment['file_path']]);
                                            $output .= "<tr>
                                                <td class='px-6 py-4 whitespace-nowrap w-auto'><a href='$signedUrl' class='text-indigo-600 hover:text-indigo-900'>{$attachment['file_name']}</a></td>
                                                <td class='px-6 py-4 whitespace-nowrap'>{$attachment['description']}</td>
                                            </tr>";
                                        }
                                    }
                                    $output .= "</tbody></table>";
                                    return new HtmlString($output);
                                    })
                                    ->label('Attachments'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Note: You can currently only edit requests made in this table. Responses are currently only visible on the Audit Item assessment page. This will be addressed in a future release.')
            ->columns([
                TextColumn::make('id')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->label('ID'),
                TextColumn::make('details')
                    ->label('Request Details')
                    ->wrap(),
                TextColumn::make('responses.status')
                    ->label('Responses')
                    ->badge(),
                TextColumn::make('assignedTo.name'),
                TextColumn::make('created_at'),
//                TextColumn::make('status')->badge(),
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
                    ->after(function (DataRequest $record, Tables\Actions\Action $action) {
                        DataRequestResource::createResponses($record);
                    }),
                Tables\Actions\Action::make('import_irl')
                    ->label('Import IRL')
                    ->color('primary')
                    ->disabled(function () {
                        return $this->getOwnerRecord()->status != WorkflowStatus::INPROGRESS;
                    })
                    ->action(function () {
                        $audit = $this->getOwnerRecord();
                        return redirect()->route('filament.app.resources.audits.import-irl', $audit);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading("View Data Request")
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
