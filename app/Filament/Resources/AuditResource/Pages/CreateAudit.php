<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Enums\WorkflowStatus;
use App\Filament\Forms\Components\ActionableMultiselectTwoSides;
use App\Filament\Resources\AuditResource;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Program;
use App\Models\Standard;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class CreateAudit extends CreateRecord
{
    use HasWizard;

    protected static string $resource = AuditResource::class;

    public function getSteps(): array
    {
        return [
            Step::make('Audit Type')
                ->columns(2)
                ->schema([
                    Placeholder::make('Introduction')
                        ->label('There are two Audit Types to choose from:')
                        ->columnSpanFull(),
                    Section::make('Standards Audit')
                        ->columnSpan(1)
                        ->schema(
                            [
                                Placeholder::make('Introduction')
                                    ->label('')
                                    ->content(new HtmlString('                                 
                                        <p>This audit type is used to check the compliance of the organization with a specific standard. The standard is selected from the list of standards available in the system. The audit will be performed against the controls specified in the selected standard.</p> <p><strong>Note:</strong> The standard must be set to In Scope first.</strong></p>                                       
                                ')),
                            ]
                        ),

                    Section::make('Implementations Audit')
                        ->columnSpan(1)
                        ->schema(
                            [
                                Placeholder::make('Introduction')
                                    ->label('')
                                    ->content(new HtmlString('
                                   <p>This kind of audit is used to audit the implementations of controls in your organization. Implementations are selected from your total list of implemented controls and setup for audit.</p>
                                ')),
                            ]
                        ),

                    Select::make('audit_type')
                        ->label('Select Audit Type')
                        ->columns(1)
                        ->required()
                        ->options([
                            'standards' => 'Standards Audit',
                            'implementations' => 'Implementations Audit',
                            'program' => 'Program Audit',
                        ])
                        ->native(false)
                        ->live(),
                    Select::make('sid')
                        ->columns(1)
                        ->label('Standard to Audit')
                        ->options(Standard::where('status', 'In Scope')->pluck('name', 'id'))
                        ->columns(1)
                        ->searchable()
                        ->native(false)
                        ->visible(fn (Get $get) => $get('audit_type') == 'standards'),
                    Select::make('program_id')
                        ->label('Program to Audit')
                        ->relationship('program', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get) => $get('audit_type') == 'program'),
                ]),

            Step::make('Basic Information')
                ->columns(2)
                ->schema([
                    TextInput::make('title')
                        ->hint('Give the audit a distinctive title.')
                        ->required()
                        ->columns(1)
                        ->placeholder('2023 SOC 2 Type II Audit')
                        ->maxLength(255),
                    Select::make('manager_id')
                        ->label('Audit Manager')
                        ->required()
                        ->hint('Who will be managing this audit?')
                        ->options(User::query()->pluck('name', 'id')->toArray())
                        ->columns(1)
                        ->default(fn () => auth()->id())
                        ->searchable(),
                    Textarea::make('description')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                    DatePicker::make('start_date')
                        ->default(now())
                        ->required(),
                    DatePicker::make('end_date')
                        ->default(now()->addDays(30))
                        ->required(),
                    Hidden::make('status')
                        ->default(WorkflowStatus::NOTSTARTED),
                    AuditResource::taxonomySelect('Department', 'department')
                        ->nullable()
                        ->columnSpan(1),
                    AuditResource::taxonomySelect('Scope', 'scope')
                        ->nullable()
                        ->columnSpan(1),
                ]),

            Step::make('Audit Details')
                ->schema([

                    Grid::make(1)
                        ->schema(
                            function (Get $get): array {
                                $audit_type = $get('audit_type');
                                $standard_id = $get('sid');
                                $implementation_ids = $get('implementation_ids');
                                $allDefaults = [];

                                $metadata = [];

                                if ($audit_type == 'standards') {
                                    $controlModels = Control::where('standard_id', '=', $standard_id)->get();
                                    $controls = $controlModels->mapWithKeys(function ($control) {
                                        return [$control->id => $control->code.' - '.$control->title];
                                    })->toArray();
                                    $metadata = $controlModels->mapWithKeys(function ($control) {
                                        $latestAudit = $control->latestCompletedAuditItem();

                                        return [$control->id => [
                                            'effectiveness' => $control->getEffectiveness(),
                                            'applicability' => $control->applicability,
                                            'control_owner_id' => $control->control_owner_id,
                                            'standard_id' => $control->standard_id,
                                            'last_assessed_at' => $latestAudit?->updated_at?->toDateTimeString(),
                                        ]];
                                    })->toArray();
                                } elseif ($audit_type == 'implementations') {
                                    $implementationModels = Implementation::query()->get();
                                    $controls = $implementationModels->mapWithKeys(function ($implementation) {
                                        return [$implementation->id => $implementation->code.' - '.$implementation->title];
                                    })->toArray();
                                    $metadata = $implementationModels->mapWithKeys(function ($implementation) {
                                        $latestAudit = $implementation->completedAuditItems()
                                            ->latest('updated_at')
                                            ->first();

                                        return [$implementation->id => [
                                            'effectiveness' => $implementation->getEffectiveness(),
                                            'status' => $implementation->status,
                                            'implementation_owner_id' => $implementation->implementation_owner_id,
                                            'last_assessed_at' => $latestAudit?->updated_at?->toDateTimeString(),
                                        ]];
                                    })->toArray();
                                } elseif ($audit_type == 'program') {
                                    $program_id = $get('program_id');
                                    if ($program_id) {
                                        $program = Program::find($program_id);
                                        $controlModels = $program->getAllControls();
                                        $controls = $controlModels->mapWithKeys(function ($control) {
                                            return [$control->id => $control->code.' - '.$control->title];
                                        })->toArray();
                                        $metadata = $controlModels->mapWithKeys(function ($control) {
                                            $latestAudit = $control->latestCompletedAuditItem();

                                            return [$control->id => [
                                                'effectiveness' => $control->getEffectiveness(),
                                                'applicability' => $control->applicability,
                                                'control_owner_id' => $control->control_owner_id,
                                                'standard_id' => $control->standard_id,
                                                'last_assessed_at' => $latestAudit?->updated_at?->toDateTimeString(),
                                            ]];
                                        })->toArray();
                                    } else {
                                        $controls = [];
                                    }
                                } else {
                                    $controls = [];
                                }

                                return [
                                    ActionableMultiselectTwoSides::make('controls')
                                        ->options($controls)
                                        ->optionsMetadata($metadata)
                                        ->selectableLabel('Available Items')
                                        ->selectedLabel('Selected Items')
                                        ->enableSearch()
                                        ->default(! is_array($controls) ? $controls->toArray() : $controls)
                                        ->required()
                                        ->addDropdownAction(
                                            name: 'randomSelect',
                                            label: 'Random Select',
                                            type: 'random',
                                            options: [
                                                ['label' => 'Random 5', 'count' => 5],
                                                ['label' => 'Random 10', 'count' => 10],
                                                ['label' => 'Random 20', 'count' => 20],
                                            ],
                                            icon: 'heroicon-o-sparkles'
                                        )
                                        ->addDropdownAction(
                                            name: 'randomUnassessed',
                                            label: 'Random (Unassessed)',
                                            type: 'randomUnassessed',
                                            options: [
                                                ['label' => '5 Unassessed', 'count' => 5],
                                                ['label' => '10 Unassessed', 'count' => 10],
                                                ['label' => 'All Unassessed', 'count' => 0],
                                            ],
                                            icon: 'heroicon-o-question-mark-circle'
                                        )
                                        ->addDropdownAction(
                                            name: 'oldestAssessed',
                                            label: 'Oldest Assessed',
                                            type: 'oldest',
                                            options: [
                                                ['label' => '5 Oldest', 'count' => 5],
                                                ['label' => '10 Oldest', 'count' => 10],
                                                ['label' => '20 Oldest', 'count' => 20],
                                            ],
                                            icon: 'heroicon-o-clock'
                                        ),
                                ];
                            }),
                ]),

        ];
    }

    protected function afterCreate(): void
    {
        if (is_array($this->data['controls']) && count($this->data['controls']) > 0) {
            foreach ($this->data['controls'] as $control) {
                $audit_item = $this->record->auditItems()->create([
                    'status' => 'Not Started',
                    'applicability' => 'Applicable',
                    'effectiveness' => 'Not Assessed',
                    'audit_id' => $this->record->id,
                    'user_id' => $this->data['manager_id'],
                ]);

                switch (strtolower($this->data['audit_type'])) {
                    case 'standards':
                        $audit_item->auditable()->associate(Control::find($control));
                        break;
                    case 'implementations':
                        $audit_item->auditable()->associate(Implementation::find($control));
                        break;
                    case 'program':
                        $audit_item->auditable()->associate(Control::find($control));
                        break;
                }
                $audit_item->save();

            }
        }

    }
}
