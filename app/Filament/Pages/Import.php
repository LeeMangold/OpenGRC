<?php

namespace App\Filament\Pages;

use App\Models\Control;
use App\Models\Implementation;
use App\Models\Standard;
use App\Models\User;
use Exception;
use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\HtmlString;
use League\Csv\Reader;
use Notification;

class Import extends Page
{
    use HasWizard, InteractsWithForms; // , InteractsWithRecord;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationGroup = 'Tools';

    protected static ?string $navigationLabel = 'Import';

    protected static ?string $navigationDescription = 'Import data from a file';

    protected static ?string $label = 'Import';

    protected static string $view = 'filament.pages.import';

    // Hide this page from the navigation
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data_file;

    public ?string $import_type = 'controls';

    public ?string $data_file_path;

    public bool $isDataFileValid = false;

    public ?array $data_file_data;

    public ?array $data = [];

    public ?array $finalData = [];

    public $currentItems;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Setup Import')
                        ->id('data-file')
                        ->icon('heroicon-m-document')
                        ->schema([
                            Select::make('import_type')
                                ->label('Import Type')
                                ->live()
                                ->options([
                                    'controls' => 'Controls',
                                    'implementations' => 'Implementations',
                                ])
                                ->helperText(fn (Get $get): ?HtmlString => match ($get('import_type')) {
                                    'controls' => new HtmlString('
                                        <strong>Required fields:</strong> code, title, standard_code<br>
                                        <strong>Optional fields:</strong> description, discussion, test, owner<br>
                                        <a href="/resources/sample-controls-import.csv" download class="text-primary-600 hover:text-primary-500 underline">Download sample Controls CSV template</a>
                                    '),
                                    'implementations' => new HtmlString('
                                        <strong>Required fields:</strong> code, title<br>
                                        <strong>Optional fields:</strong> details, notes, test plan, owner, map-control<br>
                                        <a href="/resources/sample-implementations-import.csv" download class="text-primary-600 hover:text-primary-500 underline">Download sample Implementations CSV template</a>
                                    '),
                                    default => null,
                                })
                                ->required(),
                            FileUpload::make('data_file')
                                ->label('Data File')
                                ->required()
                                ->disk('private')
                                ->directory('imports')
                                ->acceptedFileTypes(['text/csv'])
                                ->rules([])
                                ->afterStateUpdated(function ($state, Get $get) {
                                    if ($state) {
                                        $this->data_file_path = $state->getPathname();
                                        $this->isDataFileValid = $this->validateDataFile();
                                    }
                                }),

                        ]),
                    Wizard\Step::make('Confirm Import')
                        ->label('Confirm Import')
                        ->schema([
                            Placeholder::make('Changes to be made')
                                ->columnSpanFull()
                                ->label(new HtmlString('
                                        <p><strong>Changes to be made</strong></p>'))
                                ->content(new HtmlString($this->finalData))
                                ->view('filament.pages.import-data-table', [
                                    'data' => $this->finalData ?? [],
                                    'users' => $this->users ?? [],
                                    'import_type' => $this->import_type,
                                ]),
                        ]),
                ])
                    ->submitAction(new HtmlString('<button class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-action fi-ac-btn-action" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);" type="submit">Import Records</button>')),
            ]);
    }

    public function validateDataFile(): bool
    {

        if ($this->import_type === 'controls') {
            $this->currentItems = Control::all();

            return $this->validateControlFile();
        } elseif ($this->import_type === 'implementations') {
            $this->currentItems = Implementation::all();

            return $this->validateImplementationFile();
        }

        return false;
    }

    public function validateControlFile(): bool
    {
        try {
            $reader = Reader::createFromPath($this->data_file_path, 'r');
            $reader->setHeaderOffset(0);
            $headers = $reader->getHeader();
            $normalizedHeaders = array_map(function ($header) {
                return strtolower(trim($header));
            }, $headers);

            $requiredHeaders = ['code', 'title', 'standard_code'];
            $missingHeaders = array_diff($requiredHeaders, $normalizedHeaders);

            if (! empty($missingHeaders)) {
                $err = new HtmlString('Control File missing required fields: '.implode(', ', $missingHeaders).'<br><br>Please correct your file and reupload');
                $this->addError('data_file', $err);

                return false;
            } else {
                $this->resetErrorBag('data_file');
                $this->data_file_data = iterator_to_array($reader->getRecords());
                $this->preProcessData();

                return true;
            }

        } catch (Exception $e) {
            $this->addError('data_file', 'Invalid file: '.$e->getMessage());

            return false;
        }
    }

    public function validateImplementationFile(): bool
    {
        try {
            $reader = Reader::createFromPath($this->data_file_path, 'r');
            $reader->setHeaderOffset(0);
            $headers = $reader->getHeader();
            $normalizedHeaders = array_map(function ($header) {
                return strtolower(trim($header));
            }, $headers);

            $requiredHeaders = ['title'];
            $missingHeaders = array_diff($requiredHeaders, $normalizedHeaders);

            if (! empty($missingHeaders)) {
                $err = new HtmlString('Implementation File missing fields: '.implode(', ', $missingHeaders).'<br><br>Please correct your file and reupload');
                $this->addError('data_file', $err);

                return false;
            } else {
                $this->resetErrorBag('data_file');
                $this->data_file_data = iterator_to_array($reader->getRecords());
                $this->preProcessData();

                return true;
            }

        } catch (Exception $e) {
            $this->addError('data_file', 'Invalid file: '.$e->getMessage());

            return false;
        }
    }

    public function preProcessData(): bool
    {
        $has_errors = false;
        $error_array = [];

        try {
            $this->finalData = [];
            foreach ($this->data_file_data as $index => $row) {
                $finalRecord = [];

                // If the item exists, update it
                if ($this->currentItems->where('code', $row['code'])->count() > 0) {
                    $finalRecord['_ACTION'] = 'UPDATE';
                } // else, create it
                else {
                    $finalRecord['_ACTION'] = 'CREATE';
                }

                $finalRecord['code'] = $row['code'];
                $finalRecord['title'] = $row['title'];

                if ($this->import_type === 'controls') {
                    // Controls use: description, discussion, test, standard_code
                    // Resolve standard_code to standard_id
                    $standardCode = $row['standard_code'] ?? null;
                    $standard = null;
                    if ($standardCode) {
                        $standard = Standard::where('code', $standardCode)->first();
                        if (! $standard) {
                            $has_errors = true;
                            $error_array[] = 'Row '.($index + 1).": Standard code '{$standardCode}' not found";
                        }
                    }

                    $finalRecord['standard_code'] = $standardCode;
                    $finalRecord['standard_id'] = $standard?->id;
                    $finalRecord['description'] = $row['description'] ?? '';
                    $finalRecord['discussion'] = $row['discussion'] ?? '';
                    $finalRecord['test'] = $row['test'] ?? '';
                    $finalRecord['owner'] = $row['owner'] ?? '';
                } elseif ($this->import_type === 'implementations') {
                    // Implementations use: details, notes, test_plan
                    $finalRecord['details'] = $row['details'] ?? '';
                    $finalRecord['notes'] = $row['notes'] ?? '';
                    $finalRecord['test_plan'] = $row['test plan'] ?? '';
                    $finalRecord['owner'] = $row['owner'] ?? '';
                    $finalRecord['map-control'] = $row['map-control'] ?? '';
                }

                $this->finalData[] = $finalRecord;

            }

            if ($has_errors) {
                $this->isDataFileValid = false;
                $this->error_string = implode(' | ', $error_array);
                $this->addError('data_file', $this->error_string);

                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->addError('data_file', 'Error pre-processing data: '.$e->getMessage());

            Notification::make()
                ->title('Error validating data: '.$e->getMessage())
                ->danger()
                ->send();

            return false;
        }
    }

    public function save()
    {
        foreach ($this->finalData as $row) {

            $owner = User::where('email', $row['owner'])->first();

            if ($row['_ACTION'] == 'CREATE') {

                if ($this->import_type === 'controls') {
                    $control = new Control;
                    $control->code = $row['code'];
                    $control->title = $row['title'];
                    $control->standard_id = $row['standard_id'];
                    $control->description = $row['description'];
                    $control->discussion = $row['discussion'];
                    $control->test = $row['test'];
                    $control->control_owner_id = $owner->id ?? null;
                    $control->save();
                } elseif ($this->import_type === 'implementations') {
                    $mappedControls = $this->getMappedControls($row['map-control']);

                    $implementation = new Implementation;
                    $implementation->code = $row['code'];
                    $implementation->title = $row['title'];
                    $implementation->details = $row['details'];
                    $implementation->notes = $row['notes'];
                    $implementation->test_plan = $row['test_plan'];
                    $implementation->implementation_owner_id = $owner->id ?? null;
                    $implementation->save();
                    if (empty($mappedControls)) {
                        $implementation->controls()->detach();
                    } else {
                        $implementation->controls()->syncWithoutDetaching($mappedControls);
                    }
                }

            } elseif ($row['_ACTION'] == 'UPDATE') {
                if ($this->import_type === 'controls') {
                    $control = $this->currentItems->where('code', $row['code'])->first();
                    $control->title = $row['title'];
                    $control->standard_id = $row['standard_id'];
                    $control->description = $row['description'];
                    $control->discussion = $row['discussion'];
                    $control->test = $row['test'];
                    $control->control_owner_id = $owner->id ?? null;
                    $control->update();
                } elseif ($this->import_type === 'implementations') {
                    $mappedControls = $this->getMappedControls($row['map-control']);

                    $implementation = $this->currentItems->where('code', $row['code'])->first();
                    $implementation->title = $row['title'];
                    $implementation->details = $row['details'];
                    $implementation->notes = $row['notes'];
                    $implementation->test_plan = $row['test_plan'];
                    $implementation->implementation_owner_id = $owner->id ?? null;
                    if (empty($mappedControls)) {
                        $implementation->controls()->detach();
                    } else {
                        $implementation->controls()->syncWithoutDetaching($mappedControls);
                    }
                    $implementation->update();
                }
            }
        }

        return redirect()->route('filament.app.pages.import');
    }

    /**
     * Takes a map-control string, splits it, and returns an array of Control objects if they exist.
     */
    protected function getMappedControls(string $mapControlsString): array
    {
        $codes = preg_split('/[\s,]+/', $mapControlsString, -1, PREG_SPLIT_NO_EMPTY);
        $controls = [];
        foreach ($codes as $code) {
            $control = Control::where('code', $code)->first();
            if ($control) {
                $controls[] = $control;
            }
        }

        return $controls;
    }

    public function getFormActions(): array
    {
        return [

        ];
    }
}
