<?php

namespace Modules\DataManager\Filament\Admin\Pages;

use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Modules\DataManager\Services\EntityRegistry;
use Modules\DataManager\Services\ImportService;
use Modules\DataManager\Services\SchemaInspector;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @property mixed $form
 */
class Import extends Page implements HasForms
{
    use HasWizard;
    use InteractsWithForms;

    // Exclude form from Livewire serialization to prevent JSON errors
    protected $except = ['form'];

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Manager';

    protected static ?string $navigationLabel = 'Import Data';

    protected static ?string $title = 'Import Data';

    protected static ?int $navigationSort = 20;

    protected string $view = 'datamanager::filament.admin.pages.import';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('Import Data');
    }

    public ?string $entity_type = null;

    //public $upload_file;
    public ?array $upload_file = [];

    public ?string $upload_file_path = null;

    public ?array $csv_headers = [];

    public ?array $column_mapping = [];

    public ?array $preview_data = [];

    public ?array $db_fields = [];

    public ?array $required_fields = [];

    public int $total_rows = 0;

    public ?array $mapping_errors = [];

    public bool $is_importing = false;

    public ?array $import_result = null;

    public function mount(): void
    {
        $this->form->fill(); // @phpstan-ignore property.notFound
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Wizard::make([
                Step::make('Select Entity')
                    ->id('entity-selection')
                    ->icon('heroicon-m-rectangle-stack')
                    ->description('Choose the type of data to import')
                    ->schema([
                        Select::make('entity_type')
                            ->label('What would you like to import?')
                            ->placeholder('Select an entity type...')
                            ->options(fn () => app(EntityRegistry::class)->getGroupedOptions())
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->resetImportState();
                                if ($state) {
                                    $this->loadFieldConfiguration($state);
                                }
                            }),

                        Placeholder::make('field_info')
                            ->label('')
                            ->visible(fn (Get $get) => $get('entity_type') !== null)
                            ->content(function () {
                                $requiredCount = count($this->required_fields);
                                $totalCount = count($this->db_fields);

                                return new HtmlString("
                                    <div class=\"p-4 bg-gray-50 dark:bg-gray-800 rounded-lg\">
                                        <p class=\"text-sm text-gray-600 dark:text-gray-400\">
                                            <strong>{$totalCount}</strong> fields available for import
                                            (<strong>{$requiredCount}</strong> required)
                                        </p>
                                        <div class=\"mt-3\">
                                            <button type=\"button\" wire:click=\"downloadTemplate\" class=\"inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-primary-600 bg-primary-50 hover:bg-primary-100 dark:text-primary-400 dark:bg-primary-900/20 dark:hover:bg-primary-900/40 rounded-lg transition-colors\">
                                                <svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4\"></path>
                                                </svg>
                                                Download CSV Template
                                            </button>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

                Step::make('Upload File')
                    ->id('file-upload')
                    ->icon('heroicon-m-document-arrow-up')
                    ->description('Upload your CSV file')
                    ->schema([
                        FileUpload::make('upload_file')
                            ->label('CSV File')
                            ->required()
                            //->acceptedFileTypes(['text/csv'])
                            //->rules([])
                            ->helperText('Upload a CSV file with headers in the first row.')
                            ->afterStateUpdated(function ($state) {
                                if ($state) {
                                    //Debug
                                    $this->parseUploadedFile($state);
                                }
                            }),

                        Placeholder::make('file_info')
                            ->label('')
                            ->visible(fn () => $this->total_rows > 0)
                            ->content(function () {
                                $headerList = implode(', ', array_slice($this->csv_headers, 0, 10));
                                if (count($this->csv_headers) > 10) {
                                    $headerList .= '... and '.(count($this->csv_headers) - 10).' more';
                                }

                                return new HtmlString("
                                    <div class=\"p-4 bg-green-50 dark:bg-green-900/20 rounded-lg\">
                                        <p class=\"text-sm font-medium text-green-800 dark:text-green-200\">
                                            File parsed successfully!
                                        </p>
                                        <p class=\"text-sm text-green-700 dark:text-green-300 mt-1\">
                                            <strong>{$this->total_rows}</strong> rows found with
                                            <strong>".count($this->csv_headers)."</strong> columns
                                        </p>
                                        <p class=\"text-xs text-green-600 dark:text-green-400 mt-2\">
                                            Headers: {$headerList}
                                        </p>
                                    </div>
                                ");
                            }),
                    ]),

                Step::make('Map Columns')
                    ->id('column-mapping')
                    ->icon('heroicon-m-arrows-right-left')
                    ->description('Map CSV columns to database fields')
                    ->schema([
                        Section::make('Column Mapping')
                            ->description('Map each CSV column to the corresponding database field. Required fields are marked with *.')
                            ->schema([
                                ViewField::make('column_mapper')
                                    ->view('datamanager::filament.admin.pages.partials.column-mapper'),
                            ]),

                        Placeholder::make('mapping_validation')
                            ->label('')
                            ->visible(fn () => ! empty($this->mapping_errors))
                            ->content(function () {
                                $errors = implode('</li><li>', $this->mapping_errors);

                                return new HtmlString("
                                    <div class=\"p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg\">
                                        <p class=\"text-sm font-medium text-danger-800 dark:text-danger-200\">
                                            Please fix the following issues:
                                        </p>
                                        <ul class=\"text-sm text-danger-700 dark:text-danger-300 mt-2 list-disc list-inside\">
                                            <li>{$errors}</li>
                                        </ul>
                                    </div>
                                ");
                            }),
                    ]),

                Step::make('Import')
                    ->id('import')
                    ->icon('heroicon-m-arrow-up-tray')
                    ->description('Review and import data')
                    ->schema([
                        Section::make('Preview')
                            ->description('First 5 rows of data with your column mapping')
                            ->schema([
                                ViewField::make('preview_table')
                                    ->view('datamanager::filament.admin.pages.partials.import-preview'),
                            ]),

                        Placeholder::make('import_summary')
                            ->label('Import Summary')
                            ->content(function () {
                                $mappedCount = is_array($this->column_mapping) ? count(array_filter($this->column_mapping)) : 0;
                                $totalRows = $this->total_rows ?? 0;

                                return new HtmlString("
                                    <div class=\"space-y-2\">
                                        <p><strong>Total rows:</strong> {$totalRows}</p>
                                        <p><strong>Columns mapped:</strong> {$mappedCount}</p>
                                        <p class=\"text-sm text-gray-500\">
                                            Existing records with matching identifiers will be updated.
                                            New records will be created.
                                        </p>
                                    </div>
                                ");
                            }),

                        Placeholder::make('import_result')
                            ->label('')
                            ->visible(fn () => $this->import_result !== null)
                            ->content(function () {
                                if ($this->import_result === null) {
                                    return '';
                                }

                                $result = $this->import_result;
                                $statusClass = $result['error_count'] > 0 ? 'warning' : 'success';

                                $html = "
                                    <div class=\"p-4 bg-{$statusClass}-50 dark:bg-{$statusClass}-900/20 rounded-lg\">
                                        <p class=\"text-lg font-medium text-{$statusClass}-800 dark:text-{$statusClass}-200\">
                                            Import Complete
                                        </p>
                                        <div class=\"mt-2 space-y-1\">
                                            <p class=\"text-sm text-{$statusClass}-700 dark:text-{$statusClass}-300\">
                                                <strong>Successful:</strong> {$result['success_count']} records
                                            </p>
                                            <p class=\"text-sm text-{$statusClass}-700 dark:text-{$statusClass}-300\">
                                                <strong>Errors:</strong> {$result['error_count']} records
                                            </p>
                                ";

                                if ($result['rolled_back']) {
                                    $html .= '
                                            <p class="text-sm text-danger-700 dark:text-danger-300 mt-2">
                                                <strong>Note:</strong> Changes were rolled back due to high error rate.
                                            </p>
                                    ';
                                }

                                if (! empty($result['errors'])) {
                                    $html .= '<div class="mt-3"><p class="text-sm font-medium">Error Details:</p><ul class="text-xs mt-1 max-h-40 overflow-y-auto">';
                                    foreach (array_slice($result['errors'], 0, 10) as $row => $error) {
                                        $html .= "<li>Row {$row}: {$error}</li>";
                                    }
                                    if (count($result['errors']) > 10) {
                                        $html .= '<li>... and '.(count($result['errors']) - 10).' more errors</li>';
                                    }
                                    $html .= '</ul></div>';
                                }

                                $html .= '</div></div>';

                                return new HtmlString($html);
                            }),
                    ]),
            ])
                ->submitAction($this->getSubmitActionHtml()),
        ]);
    }

    protected function resetImportState(): void
    {
        $this->upload_file = null;
        $this->upload_file_path = null;
        $this->csv_headers = [];
        $this->column_mapping = [];
        $this->preview_data = [];
        $this->db_fields = [];
        $this->required_fields = [];
        $this->total_rows = 0;
        $this->mapping_errors = [];
        $this->import_result = null;
    }

    protected function loadFieldConfiguration(string $entityType): void
    {
        $modelClass = app(EntityRegistry::class)->getModel($entityType);
        if (! $modelClass) {
            return;
        }

        $inspector = app(SchemaInspector::class);
        $this->db_fields = $inspector->getImportableFields($modelClass);
        $this->required_fields = $inspector->getRequiredFields($modelClass);
    }

    protected function parseUploadedFile(mixed $state): void
    {
        try {
            // Handle different state types from Filament FileUpload
            $path = null;

            if ($state instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                // Fresh upload - get the real path
                $path = $state->getRealPath();
            } elseif (is_string($state)) {
                // Already stored file path
                $path = storage_path('app/'.$state);
            } elseif (is_array($state) && ! empty($state)) {
                // Array of files - take the first one
                $firstFile = reset($state);
                if ($firstFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    $path = $firstFile->getRealPath();
                } elseif (is_string($firstFile)) {
                    $path = storage_path('app/'.$firstFile);
                }
            }

            if (! $path || ! file_exists($path)) {
                $this->addError('upload_file', 'Could not read the uploaded file.');

                return;
            }

            $this->upload_file_path = $path;

            // Validate file encoding before parsing
            if (! $this->validateFileEncoding($path)) {
                return;
            }

            $importService = app(ImportService::class);
            $parsed = $importService->parseFile($path);

            $this->csv_headers = $parsed['headers'];
            $this->preview_data = $parsed['preview'];
            $this->total_rows = $parsed['total_rows'];

            // Auto-map columns
            $this->column_mapping = $importService->autoMapColumns($this->csv_headers, $this->db_fields);

            // Validate initial mapping
            $this->validateMapping();

            $this->resetErrorBag('upload_file');
        } catch (\Exception $e) {
            $this->addError('upload_file', 'Error parsing file: '.$e->getMessage());
        }
    }

    /**
     * Validate that the file is properly encoded UTF-8 without special characters that would break parsing.
     */
    protected function validateFileEncoding(string $path): bool
    {
        try {
            $contents = file_get_contents($path);

            if ($contents === false) {
                $this->addError('upload_file', 'Unable to read file contents.');

                return false;
            }

            // Remove BOM if present
            $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

            // Check if the file is valid UTF-8
            if (! mb_check_encoding($contents, 'UTF-8')) {
                $this->addError('upload_file', new HtmlString(
                    '<strong>File encoding error:</strong> The file contains non-UTF8 characters.<br><br>'.
                    'This often happens with files exported from Excel or containing special characters like em-dashes (—), curly quotes, or other non-standard characters.<br><br>'.
                    '<strong>To fix:</strong> Open the file in a text editor and save it as UTF-8, or re-export from your spreadsheet application using UTF-8 encoding.'
                ));

                return false;
            }

            // Check if JSON encoding would fail (catches other problematic characters)
            $testEncode = json_encode($contents);
            if ($testEncode === false) {
                $this->addError('upload_file', new HtmlString(
                    '<strong>File contains invalid characters:</strong> The file contains characters that cannot be processed.<br><br>'.
                    'Please check for special characters, control characters, or binary data in your CSV file.'
                ));

                return false;
            }

            return true;

        } catch (\Exception $e) {
            $this->addError('upload_file', 'Error checking file encoding: '.$e->getMessage());

            return false;
        }
    }

    public function updateMapping(int $csvIndex, ?string $dbField): void
    {
        if ($dbField === '' || $dbField === null) {
            unset($this->column_mapping[$csvIndex]);
        } else {
            $this->column_mapping[$csvIndex] = $dbField;
        }

        $this->validateMapping();
    }

    protected function validateMapping(): void
    {
        $importService = app(ImportService::class);
        $this->mapping_errors = $importService->validateMapping($this->column_mapping, $this->required_fields);
    }

    public function save(): void
    {
        if (! empty($this->mapping_errors)) {
            Notification::make()
                ->title('Please fix mapping errors before importing')
                ->danger()
                ->send();

            return;
        }

        if (! $this->upload_file_path || ! file_exists($this->upload_file_path)) {
            Notification::make()
                ->title('Upload file not found. Please re-upload.')
                ->danger()
                ->send();

            return;
        }

        $this->is_importing = true;

        try {
            $importService = app(ImportService::class);

            $result = $importService->import(
                $this->entity_type,
                $this->upload_file_path,
                $this->column_mapping
            );

            $this->import_result = $result->toArray();

            if ($result->hasErrors()) {
                Notification::make()
                    ->title('Import completed with errors')
                    ->body("{$result->successCount()} records imported, {$result->errorCount()} failed")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Import completed successfully')
                    ->body("{$result->successCount()} records imported")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->import_result = [
                'success_count' => 0,
                'error_count' => 1,
                'errors' => ['Critical error: '.$e->getMessage()],
                'rolled_back' => true,
            ];
        } finally {
            $this->is_importing = false;
        }
    }

    public function getFormActions(): array
    {
        return [];
    }

    protected function getSubmitActionHtml(): HtmlString
    {
        $isComplete = $this->import_result !== null;
        $disabledAttr = $isComplete ? 'disabled' : '';
        $buttonText = $isComplete ? 'Import Complete' : 'Start Import';
        $iconHtml = $isComplete
            ? '<svg class="w-5 h-5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
            : '<svg class="w-5 h-5 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>';

        return new HtmlString("
            <button type=\"submit\" wire:loading.attr=\"disabled\" {$disabledAttr} class=\"fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 disabled:opacity-50 disabled:cursor-not-allowed\" style=\"--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);\">
                <span wire:loading.remove>
                    {$iconHtml}
                    {$buttonText}
                </span>
                <span wire:loading>
                    <svg class=\"animate-spin h-5 w-5 mr-1 inline\" xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\">
                        <circle class=\"opacity-25\" cx=\"12\" cy=\"12\" r=\"10\" stroke=\"currentColor\" stroke-width=\"4\"></circle>
                        <path class=\"opacity-75\" fill=\"currentColor\" d=\"M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z\"></path>
                    </svg>
                    Importing...
                </span>
            </button>
        ");
    }

    public function downloadTemplate(): StreamedResponse
    {
        if (! $this->entity_type || empty($this->db_fields)) {
            Notification::make()
                ->title('Please select an entity type first')
                ->danger()
                ->send();

            return response()->streamDownload(fn () => '', 'error.csv');
        }

        $entityRegistry = app(EntityRegistry::class);
        $entityConfig = $entityRegistry->get($this->entity_type);
        $entityLabel = $entityConfig['label'] ?? $this->entity_type;

        // Build CSV header row
        $headers = array_map(fn ($field) => $field['name'], $this->db_fields);

        // Build a second row with field descriptions (type hints)
        $descriptions = [];
        foreach ($this->db_fields as $field) {
            $desc = [];

            // Indicate if required
            if ($field['required']) {
                $desc[] = 'REQUIRED';
            }

            // Add type info
            $type = $field['field_type'];
            if ($field['is_primary']) {
                $desc[] = 'Optional - provide to update existing, omit to create new';
            } elseif ($type === 'enum' && isset($field['enum_class'])) {
                $enumResolver = app(\Modules\DataManager\Services\EnumResolver::class);
                $options = $enumResolver->getOptions($field['enum_class']);
                $desc[] = 'Options: '.implode(', ', array_keys($options));
            } elseif ($type === 'boolean') {
                $desc[] = 'true/false or 1/0';
            } elseif ($type === 'date') {
                $desc[] = 'Format: YYYY-MM-DD';
            } elseif ($type === 'datetime') {
                $desc[] = 'Format: YYYY-MM-DD HH:MM:SS';
            } elseif ($field['is_foreign_key']) {
                $desc[] = 'Foreign Key (ID)';
            } elseif ($type === 'integer') {
                $desc[] = 'Integer';
            } elseif ($type === 'decimal') {
                $desc[] = 'Decimal';
            } elseif ($type === 'text') {
                $desc[] = 'Text';
            }

            $descriptions[] = implode(' | ', $desc);
        }

        // Generate CSV content
        $csvContent = '';

        // Header row
        $csvContent .= $this->arrayToCsvRow($headers);

        // Description row (as a comment/guide - prefix with #)
        $csvContent .= $this->arrayToCsvRow(array_map(fn ($d) => $d ? "# {$d}" : '', $descriptions));

        // Empty data row for user to fill
        $csvContent .= $this->arrayToCsvRow(array_fill(0, count($headers), ''));

        $filename = str($this->entity_type)->snake()->toString().'_import_template.csv';

        return response()->streamDownload(
            fn () => print ($csvContent),
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]
        );
    }

    protected function arrayToCsvRow(array $values): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return '';
        }
        fputcsv($handle, $values);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }
}
