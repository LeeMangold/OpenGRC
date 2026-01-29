<?php

namespace Modules\DataManager\Filament\Admin\Pages;

use Filament\Actions\Concerns\HasWizard;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Modules\DataManager\Services\EntityRegistry;
use Modules\DataManager\Services\ExportService;
use Modules\DataManager\Services\SchemaInspector;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Export extends Page
{
    use HasWizard;
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Manager';

    protected static ?string $navigationLabel = 'Export Data';

    protected static ?string $title = 'Export Data';

    protected static ?int $navigationSort = 10;

    protected string $view = 'datamanager::filament.admin.pages.export';

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('Export Data');
    }

    public ?string $entity_type = null;

    public ?array $selected_fields = [];

    public ?array $selected_relationships = [];

    public ?bool $include_timestamps = false;

    public ?int $record_count = null;

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
                    ->description('Choose the type of data to export')
                    ->schema([
                        Select::make('entity_type')
                            ->label('Entity Type')
                            ->placeholder('Select an entity to export...')
                            ->options(fn () => app(EntityRegistry::class)->getGroupedOptions())
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selected_fields = [];
                                $this->selected_relationships = [];
                                $this->record_count = null;

                                if ($state) {
                                    $this->record_count = app(ExportService::class)->getRecordCount($state);
                                }
                            }),

                        Placeholder::make('record_info')
                            ->label('')
                            ->visible(fn (Get $get) => $get('entity_type') !== null)
                            ->content(function (Get $get) {
                                $count = $this->record_count ?? 0;

                                return new HtmlString("<div class=\"text-sm text-gray-600 dark:text-gray-400\">
                                    <span class=\"font-semibold\">{$count}</span> records available for export
                                </div>");
                            }),
                    ]),

                Step::make('Select Fields')
                    ->id('field-selection')
                    ->icon('heroicon-m-check-circle')
                    ->description('Choose which fields to include')
                    ->schema([
                        Section::make('Database Fields')
                            ->description('Select the fields you want to export')
                            ->schema([
                                Toggle::make('include_timestamps')
                                    ->label('Include timestamps (created_at, updated_at, deleted_at)')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->selected_fields = []),

                                CheckboxList::make('selected_fields')
                                    ->label('Fields')
                                    ->options(fn (Get $get) => $this->getFieldOptions($get('entity_type'), $get('include_timestamps')))
                                    ->columns(3)
                                    ->bulkToggleable()
                                    ->required(),
                            ]),

                        Section::make('Relationships')
                            ->description('Optionally export related record IDs')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                CheckboxList::make('selected_relationships')
                                    ->label('Many-to-Many Relationships')
                                    ->options(fn (Get $get) => $this->getRelationshipOptions($get('entity_type')))
                                    ->columns(2)
                                    ->helperText('Related record IDs will be exported as comma-separated values'),
                            ]),
                    ]),

                Step::make('Export')
                    ->id('export')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->description('Review and download')
                    ->schema([
                        Placeholder::make('export_summary')
                            ->label('Export Summary')
                            ->content(function (Get $get) {
                                $entityType = $get('entity_type');
                                $fields = $get('selected_fields') ?? [];
                                $relationships = $get('selected_relationships') ?? [];

                                $entityLabel = $entityType
                                    ? (app(EntityRegistry::class)->get($entityType)['label'] ?? $entityType)
                                    : 'Not selected';
                                $fieldCount = is_array($fields) ? count($fields) : 0;
                                $relCount = is_array($relationships) ? count($relationships) : 0;

                                return new HtmlString("
                                    <div class=\"space-y-2\">
                                        <p><strong>Entity:</strong> {$entityLabel}</p>
                                        <p><strong>Records:</strong> {$this->record_count}</p>
                                        <p><strong>Fields:</strong> {$fieldCount} selected</p>
                                        <p><strong>Relationships:</strong> {$relCount} selected</p>
                                    </div>
                                ");
                            }),
                    ]),
            ])
                ->submitAction(new HtmlString('
                    <button type="submit" class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50" style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download CSV
                    </button>
                ')),
        ]);
    }

    protected function getFieldOptions(?string $entityType, bool $includeTimestamps = false): array
    {
        if (! $entityType) {
            return [];
        }

        $modelClass = app(EntityRegistry::class)->getModel($entityType);
        if (! $modelClass) {
            return [];
        }

        $fields = app(SchemaInspector::class)->getExportableFields($modelClass, $includeTimestamps);

        return collect($fields)
            ->mapWithKeys(function ($field) {
                $label = $field['label'];

                // Add type indicator
                if ($field['field_type'] === 'enum') {
                    $label .= ' (enum)';
                } elseif ($field['is_foreign_key']) {
                    $label .= ' (FK)';
                }

                return [$field['name'] => $label];
            })
            ->toArray();
    }

    protected function getRelationshipOptions(?string $entityType): array
    {
        if (! $entityType) {
            return [];
        }

        $modelClass = app(EntityRegistry::class)->getModel($entityType);
        if (! $modelClass) {
            return [];
        }

        $relationships = app(SchemaInspector::class)->getManyToManyRelationships($modelClass);

        return collect($relationships)
            ->mapWithKeys(fn ($rel) => [$rel['name'] => $rel['label'].' IDs'])
            ->toArray();
    }

    public function save(): StreamedResponse
    {
        $data = $this->form->getState(); // @phpstan-ignore property.notFound

        if (empty($data['entity_type']) || empty($data['selected_fields'])) {
            Notification::make()
                ->title('Please select an entity and at least one field')
                ->danger()
                ->send();

            return response()->streamDownload(fn () => '', 'error.csv');
        }

        $entityType = $data['entity_type'];
        $selectedFields = $data['selected_fields'];
        $selectedRelationships = $data['selected_relationships'] ?? [];

        try {
            $exportService = app(ExportService::class);
            $csvContent = $exportService->export(
                $entityType,
                $selectedFields,
                $selectedRelationships,
                true
            );

            $filename = $entityType.'_export_'.date('Y-m-d_His').'.csv';

            Notification::make()
                ->title('Export completed successfully')
                ->success()
                ->send();

            return response()->streamDownload(
                fn () => print ($csvContent),
                $filename,
                [
                    'Content-Type' => 'text/csv',
                ]
            );
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export failed')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return response()->streamDownload(fn () => '', 'error.csv');
        }
    }

    public function getFormActions(): array
    {
        return [];
    }
}
