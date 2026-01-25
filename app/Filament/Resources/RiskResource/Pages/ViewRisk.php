<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Enums\MitigationType;
use App\Filament\Resources\RiskResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewRisk extends ViewRecord
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('applyMitigation')
                ->label('Apply Mitigation')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->form([
                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\DatePicker::make('date_implemented')
                        ->label('Date Implemented')
                        ->native(false)
                        ->default(now()),
                    Forms\Components\Select::make('strategy')
                        ->label('Mitigation Strategy')
                        ->enum(MitigationType::class)
                        ->options(MitigationType::class)
                        ->default(MitigationType::MITIGATE)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->mitigations()->create($data);

                    $this->dispatch('refreshRelationManager', manager: 'mitigations');
                })
                ->successNotificationTitle('Mitigation applied successfully'),
            Actions\EditAction::make('Update Risk')
                ->slideOver()
                ->using(function (Actions\EditAction $action, array $data, $record) {
                    // Calculate risk scores before saving
                    $data['inherent_risk'] = $data['inherent_likelihood'] * $data['inherent_impact'];
                    $data['residual_risk'] = $data['residual_likelihood'] * $data['residual_impact'];

                    // Update the record
                    $record->update($data);

                    return $record;
                })
                ->successNotificationTitle('Risk updated successfully')
                ->after(function () {
                    // Refresh the view page to show updated data
                    $this->fillForm();
                })
                ->extraModalFooterActions([
                    Actions\DeleteAction::make()
                        ->requiresConfirmation(),
                ]),
        ];
    }
}
