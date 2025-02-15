<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use App\Models\User;
use Exception;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

/**
 * @property mixed $form
 */
class ImportIrl extends Page implements HasForms
{
    use HasWizard, InteractsWithForms, InteractsWithRecord;

    protected static string $resource = AuditResource::class;

    protected static string $view = 'filament.resources.audit-resource.pages.import-irl';

    protected static ?string $title = 'IRL Import Wizard';

    public ?array $data = [];

    public ?array $irlData = [];

    public ?array $errorData = [];

    protected $except = ['form'];

    public $irl_file;

    public $currentDataRequests;

    public $auditItems;

    public $controlCodes;

    public $users;

    public ?array $finalData = [];

    public ?string $irl_file_path;

    public bool $isIrlFileValid = false;

    public ?string $error_string = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        $this->users = User::query()->pluck('name', 'id')->toArray();
        $this->currentDataRequests = DataRequest::query()->where('audit_id', $this->record->id)->get();
        $this->auditItems = $this->record->auditItems()->with('control')->get();
        $this->controlCodes = $this->auditItems->pluck('auditable.code')->toArray();
        $template_url = Storage::url('irl-template.csv');

        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('IRL File')
                        ->id('irl-file')
                        ->icon('heroicon-m-document')
                        ->schema([
                            Placeholder::make('Introduction')
                                ->columnSpanFull()
                                ->label(new HtmlString('
                                        <p><strong>Information Request List(IRL) Import Wizard</strong></p>'))
                                ->content(new HtmlString("<p>An Information Request List (IRL), sometimes called a Prepared by Client (PBC) list, is a detailed document outlining 
                                                                the specific records, evidence, and data that auditors require to conduct an effective audit. If you have received an 
                                                                IRL from your auditor, you can upload it here to create or update data requests for this audit. Make sure your IRL 
                                                                uses the template provided to ensure a successful import.</p>
                                                                <p class='mt-3'><a class='underline text-grcblue-400' href='$template_url'>IRL Template Download</a></p>")),
                            FileUpload::make('irl_file')
                                ->required()
                                ->label('IRL File')
                                ->acceptedFileTypes(['text/csv'])
                                ->rules([])
                                ->afterStateUpdated(function ($state, Get $get) {
                                    if ($state) {
                                        $this->irl_file_path = $state->getPathname();
                                        $this->isIrlFileValid = $this->validateIrlFile() && $this->validateIrlFileData();
                                    }
                                }),
                        ])
                        ->afterValidation(function () {
                            if (! $this->isIrlFileValid) {
                                $this->isIrlFileValid = $this->validateIrlFileData();
                                throw new Halt;
                            }
                        }),
                    Wizard\Step::make('Review Data')
                        ->icon('heroicon-m-document-check')
                        ->schema([
                            Placeholder::make('Changes to be made')
                                ->columnSpanFull()
                                ->label(new HtmlString('
                                        <p><strong>Changes to be made</strong></p>'))
                                ->content(new HtmlString($this->finalData))
                                ->view('filament.resources.audit-resource.pages.import-irl-table', [
                                    'data' => $this->finalData ?? [],
                                    'users' => $this->users ?? [],
                                    'currentDataRequests' => $this->currentDataRequests ?? [],
                                    'auditItems' => $this->auditItems ?? [],
                                ]),
                        ]),
                ])
                    ->submitAction(new HtmlString('<button class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);" type="submit">Import IRL Requests</button>')),
            ]);
    }

    public function validateIrlFile(): bool
    {
        try {
            $reader = Reader::createFromPath($this->irl_file_path, 'r');
            $reader->setHeaderOffset(0);
            $headers = $reader->getHeader();
            $normalizedHeaders = array_map(function ($header) {
                return strtolower(trim($header));
            }, $headers);
            $requiredHeaders = [
                'audit id',
                'request id',
                'control code',
                'details',
                'assigned to',
                'due on',
            ];
            $missingHeaders = array_diff($requiredHeaders, $normalizedHeaders);

            if (! empty($missingHeaders)) {
                $err = new HtmlString('IRL File missing fields: '.implode(', ', $missingHeaders).'<br><br>Please use the provided template and reupload your IRL');
                $this->addError('irl_file', $err);

                return false;
            } else {
                $this->resetErrorBag('irl_file');
                $this->irlData = iterator_to_array($reader->getRecords());

                return true;
            }

        } catch (Exception $e) {
            $this->addError('irl_file', 'Invalid CSV file: '.$e->getMessage());

            return false;
        }
    }

    public function validateIrlFileData(): bool
    {
        $has_errors = false;
        $error_array = [];

        try {
            $this->finalData = [];
            foreach ($this->irlData as $index => $row) {
                $finalRecord = [];

                // If the request exists, update it
                if ($this->currentDataRequests->where('id', $row['Request ID'])->count() > 0) {
                    $finalRecord['_ACTION'] = 'UPDATE';
                    $finalRecord['Request ID'] = $row['Request ID'];
                } // else, create it
                else {
                    $finalRecord['_ACTION'] = 'CREATE';
                    $finalRecord['Request ID'] = null;
                }

                // Validate that the IRL is for this audit only
                if ($row['Audit ID'] != $this->record->id) {
                    //                    $this->addError('irl_file', "Row $index: 'audit id' must match the ID of the current audit. <br> Please correct and re-upload the IRL file.");
                    //                    $this->addError('irl_file',
                    //                        new HtmlString("Row $index: 'audit id' must match the ID of the current audit."));
                    $error_array[] = "Row $index: Audit ID must match the ID of the current audit.";
                    $has_errors = true;
                    $finalRecord['Audit ID'] = 'Invalid Audit ID';
                } else {
                    $finalRecord['Audit ID'] = $row['Audit ID'];
                }

                // Validate the user is a real user
                if (! array_key_exists($row['Assigned To'], $this->users)) {
                    $error_array[] = "Row $index: no user with the id of ".$row['Assigned To'];
                    $has_errors = true;
                    $finalRecord['Assigned To'] = 'Unknown User';
                } else {
                    $finalRecord['Assigned To'] = $this->users[$row['Assigned To']];
                }

                // Validate the control exists by control code
                if (! in_array($row['Control Code'], $this->controlCodes)) {
                    //                    $this->addError('irl_file', "Row $index: no control with the code of " . $row["Control Code"]);
                    $has_errors = true;
                    $finalRecord['Control Code'] = "Control Code Not In Audit: {$row['Control Code']}";
                    $error_array[] = "Row $index: no control with the code of ".$row['Control Code'];
                } else {
                    $finalRecord['Control Code'] = $row['Control Code'];
                }

                // If $row["Details"] is empty error
                if (empty($row['Details'])) {
                    //                    $this->addError('irl_file', "Row $index: 'details' cannot be empty.");
                    $error_array[] = "Row $index: 'details' cannot be empty.";
                    $has_errors = true;
                    $finalRecord['Details'] = 'Details Cannot Be Empty';
                } else {
                    $finalRecord['Details'] = $row['Details'];
                }

                // If $row["Due On"] is not a valid date error
                if (! preg_match('/^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/', $row['Due On'])) {
                    //                    $this->addError('irl_file', "Row $index: 'due on' must be a valid date in mm/dd/yyyy format.");
                    $has_errors = true;
                    $finalRecord['Due On'] = 'Invalid Date Format';
                    $error_array[] = "Row $index: 'due on' must be a valid date in mm/dd/yyyy format.";
                } else {
                    $finalRecord['Due On'] = $row['Due On'];
                }

                if ($has_errors) {
                    $finalRecord['_ACTION'] = 'ERROR';
                }

                $this->finalData[] = $finalRecord;
            }

            if ($has_errors) {
                $this->isIrlFileValid = false;
                $this->error_string = implode(' | ', $error_array);
                $this->addError('irl_file', $this->error_string);

                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->addError('irl_file', 'Error validating data: '.$e->getMessage());

            Notification::make()
                ->title('Error validating data: '.$e->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }

    public function getFormActions(): array
    {
        return [

        ];
    }

    public function save()
    {

        foreach ($this->finalData as $row) {
            if ($row['_ACTION'] == 'CREATE') {
                $dataRequest = new DataRequest;
                $dataRequest->audit_id = $row['Audit ID'];
                $dataRequest->audit_item_id = $this->auditItems->where('auditable.code', $row['Control Code'])->first()->id;
                $dataRequest->details = $row['Details'];
                $dataRequest->assigned_to_id = array_search($row['Assigned To'], $this->users);
                $dataRequest->created_by_id = auth()->id();
                $dataRequest->save();

                // Create a Matching DataRequestResponse
                $dataRequestResponse = new DataRequestResponse;
                $dataRequestResponse->data_request_id = $dataRequest->id;
                $dataRequestResponse->requester_id = auth()->id();
                $dataRequestResponse->requestee_id = $dataRequest->assigned_to_id;
                $dataRequestResponse->due_at = $row['Due On'];
                $dataRequestResponse->save();

            } elseif ($row['_ACTION'] == 'UPDATE') {
                $dataRequest = DataRequest::find($row['Request ID']);
                $dataRequest->details = $row['Details'];
                $dataRequest->assigned_to_id = array_search($row['Assigned To'], $this->users);
                $dataRequest->save();

                $dataRequestResponse = $dataRequest->responses()->first();
                if ($dataRequestResponse) {
                    $dataRequestResponse->data_request_id = $dataRequest->id;
                    $dataRequestResponse->requestee_id = $dataRequest->assigned_to_id;
                    $dataRequestResponse->due_at = $row['Due On'];
                    $dataRequestResponse->save();
                }

            }
        }

        Notification::make()
            ->title('IRL Requests Imported Successfully')
            ->success()
            ->send();

        return redirect()->route('filament.app.resources.audits.view', $this->record->id);

    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }
}
