<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Resources\Pages\CreateRecord;

class CreateRisk extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = RiskResource::class;

    protected function afterSave()
    {
        $inherant_risk = $this->record->inherent_likelihood * $this->record->inherent_impact;
        $residual_risk = $this->record->residual_likelihood * $this->record->residual_impact;
        $this->record->inherent_risk = $inherant_risk;
        $this->record->residual_risk = $residual_risk;
        $this->record->save();

        return redirect()->route('filament.app.resources.risks.index');
    }

    public function getSteps(): array
    {
        return [
            Step::make('Risk Overview')
                ->columns(4)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->helperText('Assign a unique code to the risk')
//                        ->unique(true)
                        ->required(),
                    TextInput::make('name')
                        ->columnSpan(3)
                        ->label('Name')
                        ->helperText('Give the risk a short but descriptive name')
                        ->required(),
                    Textarea::make('description')
                        ->label('Description')
                        ->columnSpanFull()
                        ->helperText('Provide a description of the risk that will help others understand it'),
                    //                                        ]),
                    //                                Step::make('Risk Assessment')
                    //                                    ->columns(2)
                    //                                    ->schema([

                    Section::make('Inherent Likelihood')
                        ->columns(1)
                        ->columnSpan(2)
                        ->schema([

                            Placeholder::make('InherentRisk')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('Inherent likelihood is the likelihood of the risk occurring if no 
                                action is taken. Use your best judgement to determine the likelihood of this risk 
                                occurring before you applied any controls.'),
                            Placeholder::make('InherentTable')
                                ->label('Inherent Risk Assessment')
                                ->columnSpanFull()
                                ->view('components.misc.inherent_likelihood'),
                            ToggleButtons::make('inherent_likelihood')
                                ->label('Inherent Likelihood Score')
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

                    Section::make('Inherent Impact')
                        ->columns(1)
                        ->columnSpan(2)
                        ->schema([

                            Placeholder::make('InherentImpact')
                                ->hiddenLabel(true)
                                ->columnSpanFull()
                                ->content('Inherent impact is the damage that will occur if the risk does occur.
                                Use your best judgement to determine the impact of this risk occurring before you applied 
                                any controls.'),
                            Placeholder::make('InherentImpactTable')
                                ->columnSpanFull()
                                ->view('components.misc.inherent_impact'),
                            ToggleButtons::make('inherent_impact')
                                ->label('Inherent Impact Score')
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








                    Section::make('Residual Likelihood')
                        ->columns(1)
                        ->columnSpan(2)
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
                            ToggleButtons::make('inherent_likelihood')
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








                    Section::make('Residual Impact')
                        ->columns(1)
                        ->columnSpan(2)
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

                ]),





        ];
    }
}
