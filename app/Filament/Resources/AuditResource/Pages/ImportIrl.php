<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Filament\Resources\AuditResource;
use App\Models\DataRequest;
use App\Models\User;
use Exception;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\HtmlString;
use League\Csv\Reader;

class ImportIrl extends Page implements HasForms
{
    use InteractsWithRecord, InteractsWithForms, HasWizard;

    protected static string $resource = AuditResource::class;
    protected static string $view = 'filament.resources.audit-resource.pages.import-irl';

    public ?array $data = [];
    public ?array $errorData = [];
    public $irl_file;
    public $currentDataRequests;
    public $auditItems;
    public $controlCodes;
    public $users;
    public ?array $finalData = [];

    public bool $isIrlFileValid = false;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill();

    }

    public function form(Form $form): Form
    {
        $this->users = User::get()->pluck('name', 'id');
        $this->currentDataRequests = DataRequest::get()->where("audit_id", $this->record->id);
        $this->auditItems = $this->record->auditItems()->with('control')->get();
        $this->controlCodes = $this->auditItems->pluck('auditable.code')->toArray();

        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('IRL File')
                        ->schema([
                            Placeholder::make('Introduction')
                                ->columnSpanFull()
                                ->label(new HtmlString("
                                        <p><strong>IRL Import Instructions</strong></p>"))
                                ->content(new HtmlString("<p>IRL Instructions here...</p>")),
                            FileUpload::make("irl_file")
                                ->label("IRL File")
                                ->acceptedFileTypes(["text/csv"])
                                ->afterStateUpdated(function ($state) {
                                    if ($state) {
                                        $file = $state->getPathname();
                                        $this->isIrlFileValid = $this->validateIrlFile($file) && $this->validateIrlFileData();
                                    }
                                })
                                ->required(),
                        ])
                        ->afterValidation(function () {
                            if (!$this->isIrlFileValid) {
                                throw new Halt();
                            }
                        })
                    ,
                    Wizard\Step::make('Review Data')
                        ->schema([
                            Placeholder::make('Changes to be made')
                                ->columnSpanFull()
                                ->label(new HtmlString("
                                        <p><strong>Changes to be made</strong></p>"))
                                ->content(new HtmlString($this->finalData))
                                ->view('filament.resources.audit-resource.pages.import-irl-table', [
                                    'data' => $this->finalData ?? [],
                                    'users' => $this->users ?? [],
                                    'currentDataRequests' => $this->currentDataRequests ?? [],
                                    'auditItems' => $this->auditItems ?? [],
                                ])
                        ]),

                ])->submitAction(new HtmlString('<button type="submit">Import IRL Requests</button>'))
                ,

            ]);
    }

    public function validateIrlFile($file): bool
    {
        try {
            $reader = Reader::createFromPath($file, 'r');
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

            if (!empty($missingHeaders)) {
                $this->addError('irl_file', 'IRL File missing fields: ' . implode(', ', $missingHeaders));

                Notification::make()
                    ->title('IRL File missing fields: ' . implode(', ', $missingHeaders))
                    ->danger()
                    ->send();
                return false;
            } else {
                $this->resetErrorBag('irl_file');
                $this->data = iterator_to_array($reader->getRecords());
                return true;
            }

        } catch (Exception $e) {
            $this->addError('irl_file', 'Invalid CSV file: ' . $e->getMessage());

            Notification::make()
                ->title('Invalid CSV file: ' . $e->getMessage())
                ->icon('warning')
                ->warning()
                ->send();
            return false;
        }
    }


    public function validateIrlFileData(): bool
    {
        $has_errors = false;
        try {
            foreach ($this->data as $index => $row) {
                $finalRecord = [];

                // If the request exists, update it
                if ($this->currentDataRequests->where("id", $row["Request ID"])->count() > 0) {
                    $finalRecord['_ACTION'] = 'UPDATE';
                    $finalRecord['Request ID'] = $row['Request ID'];
                } // else, create it
                else {
                    $finalRecord['_ACTION'] = 'CREATE';
                    $finalRecord['Request ID'] = null;
                }

                // Validate that the IRL is for this audit only
                if ($row['Audit ID'] != $this->record->id) {
                    $this->addError('irl_file', "Row $index: 'audit id' must match the audit id.");
                    $has_errors = true;
                    $finalRecord['Audit ID'] = "Invalid Audit ID";
                } else {
                    $finalRecord['Audit ID'] = $row['Audit ID'];
                }


                // Validate the user is a real user
                if (!array_key_exists($row["Assigned To"], $this->users->toArray())) {
                    $this->addError('irl_file', "Row $index: no user with the id of " . $row["Assigned To"]);
                    $has_errors = true;
                    $finalRecord['Assigned To'] = "Unknown User";
                } else {
                    $finalRecord['Assigned To'] = $this->users[$row["Assigned To"]];
                }

                // Validate the control exists by control code
                if (!in_array($row["Control Code"], $this->controlCodes)) {
                    $this->addError('irl_file', "Row $index: no control with the code of " . $row["Control Code"]);
                    $has_errors = true;
                    $finalRecord['Control Code'] = "Control Code Not In Audit: {$row["Control Code"]}";
                } else {
                    $finalRecord['Control Code'] = $row["Control Code"];
                }

                // If $row["Details"] is empty error
                if (empty($row["Details"])) {
                    $this->addError('irl_file', "Row $index: 'details' cannot be empty.");
                    $has_errors = true;
                    $finalRecord['Details'] = "Details Cannot Be Empty";
                } else {
                    $finalRecord['Details'] = $row["Details"];
                }

                // If $row["Due On"] is not a valid date error
                if (!preg_match('/^(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])\/\d{4}$/', $row['Due On'])) {
                    $this->addError('irl_file', "Row $index: 'due on' must be a valid date in mm/dd/yyyy format.");
                    $has_errors = true;
                    $finalRecord['Due On'] = "Invalid Date Format";
                } else {
                    $finalRecord['Due On'] = $row["Due On"];
                }


                if ($has_errors) {
                    $finalRecord['_ACTION'] = 'ERROR';
                }

                $this->finalData[] = $finalRecord;
            }


            if ($has_errors) {
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->addError('irl_file', 'Error validating data: ' . $e->getMessage());

            Notification::make()
                ->title('Error validating data: ' . $e->getMessage())
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
                $dataRequest = new DataRequest();
                $dataRequest->audit_id = $row['Audit ID'];
                $dataRequest->auditItem = $this->auditItems->where('auditable.code', $row['Control Code'])->first()->id;
                $dataRequest->details = $row['Details'];
                $dataRequest->assigned_to_id = array_search($row['Assigned To'], $this->users->toArray());
//                $dataRequest->due_on = $row['Due On'];
                $dataRequest->save();
            } elseif ($row['_ACTION'] == 'UPDATE') {
                $dataRequest = DataRequest::find($row['Request ID']);
                $dataRequest->details = $row['Details'];
                $dataRequest->assigned_to_id = array_search($row['Assigned To'], $this->users->toArray());
//                $dataRequest->due_on = $row['Due On'];
                $dataRequest->save();
            }
        }

        Notification::make()
            ->title('IRL Requests Imported Successfully')
            ->success()
            ->send();

        return redirect()->route('filament.app.resources.audits.view', $this->record->id);


    }

}
