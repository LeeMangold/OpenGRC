<?php

namespace App\Filament\Resources\AuditItemResource\Pages;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Enums\ResponseStatus;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\AuditItemResource;
use App\Filament\Resources\DataRequestResource;
use App\Http\Controllers\HelperController;
use App\Mail\EvidenceRequestMail;
use App\Models\AuditItem;
use App\Models\DataRequest;
use App\Models\User;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EditAuditItem extends EditRecord
{
    public static ?string $title = 'Assess Audit Item';

    // set title to Assess Audit Item
    protected static string $resource = AuditItemResource::class;

    public function getRedirectUrl(): string
    {
        return route('filament.app.resources.audits.view', $this->record->audit_id);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Audit')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audits.view', $this->record->audit_id)),
            Action::make('request_evidence')
                ->label('Request Evidence')
                ->icon('heroicon-m-document')
                ->action(function ($data) {
                    $dataRequest = new DataRequest;
                    $dataRequest->audit_item_id = $this->record->id;
                    $dataRequest->audit_id = $this->record->audit->id;
                    $dataRequest->status = ResponseStatus::PENDING;
                    $dataRequest->created_by_id = auth()->id();
                    $dataRequest->assigned_to_id = $data['user_id'];
                    $dataRequest->details = $data['details'];
                    $dataRequest->code = $data['code'] ?? null;
                    $dataRequest->save();

                    // If code is still null after save, set to Request-{id}
                    if (! $dataRequest->code) {
                        $dataRequest->code = 'Request-'.$dataRequest->id;
                        $dataRequest->save();
                    }

                    if ($data['send_email']) {
                        $user = User::find($dataRequest->assigned_to_id);
                        $data += [
                            'email' => $user->email,
                            'name' => $user->name,
                        ];

                        try {
                            Mail::to($data['email'])->send(new EvidenceRequestMail($data['email'], $data['name']));
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Failed to send email')
                                ->danger()
                                ->send();
                        }
                    }

                    DataRequestResource::createResponses($dataRequest, $data['due_at']);

                })
                ->after(function () {
                    Notification::make()
                        ->title('Evidence Requested')
                        ->body('The evidence request has been submitted.')
                        ->success()
                        ->send();
                })
                ->schema([
                    Group::make()
                        ->columns(2)
                        ->schema([
                            Select::make('user_id')
                                ->label('Assigned To')
                                ->options(User::pluck('name', 'id'))
                                ->default($this->record->audit->manager_id)
                                ->required()
                                ->searchable(),
                            DatePicker::make('due_at')
                                ->label('Due Date')
                                ->default(HelperController::getEndDate($this->record->audit->end_date, 5))
                                ->required(),
                            Textarea::make('details')
                                ->label('Request Details')
                                ->maxLength(65535)
                                ->columnSpanFull()
                                ->required(),
                            TextInput::make('code')
                                ->label('Request Code')
                                ->maxLength(255)
                                ->helperText('Optional. If left blank, will default to Request-{id} after creation.')
                                ->nullable(),
                            Checkbox::make('send_email')
                                ->label('Send Email Notification')
                                ->default(true),
                        ]),
                ])
                ->modalHeading('Request Evidence')
                ->modalSubmitActionLabel('Submit')
                ->modalCancelActionLabel('Cancel'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Information')
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('control_code')
                            ->label('Code')
                            ->content(fn (AuditItem $record): ?string => $record->auditable->code),
                        Placeholder::make('control_title')
                            ->label('Title')
                            ->content(fn (AuditItem $record): ?string => $record->auditable->title),
                        Placeholder::make('control_desc')
                            ->label('Description')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString(optional($record->auditable)->description ?? optional($record->auditable)->details ?? ''))
                            ->columnSpanFull(),
                        Placeholder::make('control_discussion')
                            ->label($this->record->audit->audit_type == 'implementations' ? 'Test Procedure' : 'Discussion')
                            ->content(fn (AuditItem $record): HtmlString => new HtmlString(
                                $record->audit->audit_type == 'implementations'
                                    ? (optional($record->auditable)->test_procedure ?? '<em>No test procedure provided.</em>')
                                    : (optional($record->auditable)->discussion ?? '<em>No discussion provided.</em>')
                            ))
                            ->columnSpanFull(),

                    ])->columns(2)->collapsible(true),

                Section::make('Evaluation')
                    ->columnSpanFull()
                    ->schema([
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options(WorkflowStatus::class)
                            ->default('Not Started')
                            ->grouped(),
                        ToggleButtons::make('effectiveness')
                            ->label('Effectiveness')
                            ->options(Effectiveness::class)
                            ->default('Not Effective')
                            ->grouped(),
                        ToggleButtons::make('applicability')
                            ->label('Applicability')
                            ->options(Applicability::class)
                            ->default('Applicable')
                            ->grouped(),
                        RichEditor::make('auditor_notes')
                            ->columnSpanFull()
                            ->maxLength(65535)
                            ->disableToolbarButtons([
                                'image',
                                'attachFiles',
                            ])
                            ->label('Auditor Notes'),
                    ]),

                Section::make('Audit Evidence')
                    ->columnSpanFull()
                    ->schema([
                        // When auditing controls, show associated implementations
                        Placeholder::make('control.implementations')
                            ->hidden($this->record->audit->audit_type == 'implementations')
                            ->label('Documented Implementations')
                            ->view('tables.implementations-table', ['implementations' => $this->record->auditable->implementations ?? collect()])
                            ->columnSpanFull()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Implementations that are related to this control.'),
                        Placeholder::make('data_requests')
                            ->label('Data Requests')
                            ->view('tables.data-requests-table', ['requests' => $this->record->dataRequests])
                            ->columnSpanFull()
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Data Requests that have been issued.'),
                    ])
                    ->collapsible(true),
            ]);
    }
}
