<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use App\Models\Risk;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;

class CreateRisk extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = RiskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['inherent_risk'] = $data['inherent_likelihood'] * $data['inherent_impact'];
        $data['residual_risk'] = $data['residual_likelihood'] * $data['residual_impact'];

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Created new Risk';
    }

    public function getSteps(): array
    {
        return [
            Step::make('Risk Overview')
                ->columns(4)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->prefix('RISK-')
                        ->numeric()
                        ->disabled()
			->dehydrated(true)
                        ->minValue(0)
                        ->integer()
                        ->default(Risk::next())
                        ->helperText('Unique code for this risk')
                        ->unique('risks', 'code')
                        ->required(),
                    TextInput::make('name')
                        ->columnSpan(3)
                        ->maxLength(255)
                        ->label('Name')
                        ->helperText('Give the risk a short but descriptive name')
                        ->required(),
                    Textarea::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->maxLength(4096)
                        ->helperText('Provide a description of the risk that will help others understand it'),
                ]),

            Step::make('Inherent Risk')
                ->columns(2)
                ->schema([

                    Placeholder::make('InherentRisk')
                        ->hiddenLabel(true)
                        ->columnSpanFull()
                        ->content('Inherent risk is the risk that exists before you apply any controls. 
                        Use your best judgement to answer the following questions.'),

                    Section::make('How likely is this risk to occur if no action is taken?')
                        ->columnSpan(1)
                        ->columns(1)
                        ->schema([
                            Placeholder::make('InherentLikelihood')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('Inherent likelihood is the likelihood of the risk occurring if no 
                                action is taken. Use your best judgement to determine the likelihood of this risk 
                                occurring BEFORE you have applied any safeguards.'),
                            Placeholder::make('InherentImpactTable')
                                ->columnSpanFull()
                                ->view('components.misc.inherent_likelihood'),
                            ToggleButtons::make('inherent_likelihood')
                                ->columnSpanFull()
                                ->label('Inherent Likelihood Score')
                                ->options([
                                    1 => 'Very Low',
                                    2 => 'Low',
                                    3 => 'Moderate',
                                    4 => 'High',
                                    5 => 'Very High',
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                    Section::make('If this risk does occur, how severe will the impact be?')
                        ->columns(1)
                        ->columnSpan(1)
                        ->schema([
                            Placeholder::make('InherentImpact')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('Inherent impact is the damage that would occur if the risk event happens.
                                Use your best judgement to determine the impact of this risk occurring BEFORE you applied 
                                implementations.'),
                            Placeholder::make('InherentImpactTable')
                                ->columnSpanFull()
                                ->view('components.misc.inherent_impact'),
                            ToggleButtons::make('inherent_impact')
                                ->columnSpanFull()
                                ->label('Inherent Impact Score')
                                ->options([
                                    1 => 'Very Low',
                                    2 => 'Low',
                                    3 => 'Moderate',
                                    4 => 'High',
                                    5 => 'Very High',
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                ]),

            Step::make('Residual Risk')
                ->columns(2)
                ->schema([

                    Section::make('How likely is this risk to occur after your current safeguards?')
                        ->columns(1)
                        ->columnSpan(1)
                        ->schema([

                            Placeholder::make('ResidualRisk')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('Residual likelihood is the likelihood of the risk occurring if no 
                                action is taken. Use your best judgement to determine the likelihood of this risk 
                                occurring AFTER you applied controls.'),
                            Placeholder::make('ResidualTable')
                                ->columnSpanFull()
                                ->view('components.misc.inherent_likelihood'),
                            ToggleButtons::make('residual_likelihood')
                                ->label('Residual Likelihood Score')
                                ->helperText('How likely is it that this risk will impact us if we do nothing?')
                                ->options([
                                    1 => 'Very Low',
                                    2 => 'Low',
                                    3 => 'Moderate',
                                    4 => 'High',
                                    5 => 'Very High',
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                    Section::make('If this risk does occur, how severe will the impact be with your current safeguards?')
                        ->columnSpan(1)
                        ->columns(1)
                        ->schema([

                            Placeholder::make('ResidualImpact')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('Residual impact is the damage that will occur if the risk does occur.
                                Use your best judgement to determine the impact of this risk occurring AFTER you applied 
                                controls.'),
                            Placeholder::make('ResidualImpactTable')
                                ->columnSpanFull()
                                ->view('components.misc.inherent_impact'),
                            ToggleButtons::make('residual_impact')
                                ->label('Residual Impact Score')
                                ->helperText('If this risk does occur, how severe will the impact be?')
                                ->options([
                                    1 => 'Very Low',
                                    2 => 'Low',
                                    3 => 'Moderate',
                                    4 => 'High',
                                    5 => 'Very High',
                                ])
                                ->default('3')
                                ->colors(
                                    [
                                        1 => 'success',  // Very Low
                                        2 => 'info',  // Low
                                        3 => 'primary',  // Moderate
                                        4 => 'warning',  // High
                                        5 => 'danger',  // Very High
                                    ]
                                )
                                ->grouped()
                                ->required(),
                        ]),

                    Section::make('Related Implementations')
                        ->columnSpan(2)
                        ->schema([
                            Placeholder::make('implementations')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('If you already have implementatons in OpenGRC
                                that you use to control this risk, you can link them here. You
                                can relate these later if you need to.'),
                            Select::make('implementations')
                                ->label('Related Implementations')
                                ->helperText('What are we doing to mitigate this risk?')
                                ->relationship('implementations', 'title')
                                ->searchable(['title', 'code'])
                                ->multiple(),

                        ]),

                ]),

        ];
    }
}
