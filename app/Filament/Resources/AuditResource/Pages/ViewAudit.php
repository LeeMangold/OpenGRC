<?php

namespace App\Filament\Resources\AuditResource\Pages;

use App\Enums\WorkflowStatus;
use App\Filament\Resources\AuditResource;
use App\Filament\Resources\AuditResource\Components\DataRequestFilesTable;
use App\Http\Controllers\ReportController;
use App\Models\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\ActionSize;

class ViewAudit extends ViewRecord
{
    protected static string $resource = AuditResource::class;

    protected function getHeaderWidgets(): array
    {
        $record = $this->getRecord();

        switch ($record->status) {
            case WorkflowStatus::NOTSTARTED:
                $message = 'This audit has not yet been started. The audit manager can use the workflow actions above to set the state
                of this audit.';
                $bgcolor = 'grcblue';
                $fgcolor = 'white';
                $icon = "heroicon-m-information-circle";
                break;
            case WorkflowStatus::COMPLETED:
                $message = 'This audit has been marked as complete. An administrator will need to reopen the audit if necessary.';
                $bgcolor = 'grcblue';
                $fgcolor = 'white';
                $icon = "heroicon-m-exclamation-circle";
                break;
            default:
                return [];
        }

        return [
            AuditResource\Widgets\TextWidget::make([
                'message' => $message,
                'bg_color' => $bgcolor,
                'fg_color' => $fgcolor,
                'icon' => $icon,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-m-pencil')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ActionGroup::make([
                Action::make('ActionsButton')
                    ->label('Transition to In Progress')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Begin Audit')
                    ->modalDescription('Are you sure you want to begin this audit?')
                    ->modalSubmitActionLabel('Yes, start the audit!')
                    ->disabled(function (Audit $record, $livewire) {

                        if ($record->status == WorkflowStatus::INPROGRESS) {
                            return true; // Disable if already in progress
                        }

                        if (auth()->user()->hasRole('Super Admin')) {
                            return false; // Enable for Super Admin
                        }

                        if ($record->manager_id == auth()->id() && $record->status != WorkflowStatus::COMPLETED) {
                            return false; // Enable for Audit Manager
                        }

                        if ($record->status == WorkflowStatus::COMPLETED && auth()->user()->hasRole('Super Admin')) {
                            return false; // Enable for super admin when status is COMPLETED
                        }

                        return true; // Disable for everyone else

                    })
                    ->action(function (Audit $record, $livewire) {
                        $record->update(['status' => WorkflowStatus::INPROGRESS]);
                        $livewire->redirectRoute('filament.app.resources.audits.view', $record);
                    })
                ,
                Action::make('complete_audit')
                    ->label('Transition to Complete')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Complete Audit')
                    ->modalDescription('Are you sure you want to complete this audit? Ths can only be undone by an administrator.')
                    ->modalSubmitActionLabel('Yes, complete this audit!')
                    ->modalIconColor('danger')
                    ->disabled(function (Audit $record, $livewire) {

                        if (auth()->user()->hasRole('Super Admin')) {
                            return false; // Enable for Super Admin
                        }

                        if ($record->status == WorkflowStatus::INPROGRESS) {
                            return false; // Disable if already in progress
                        }

                        return true;
                    })
                    ->action(function (Audit $record, $livewire) {

                        foreach ($record->auditItems as $auditItem) {
                            $auditItem->update(['status' => WorkflowStatus::COMPLETED]);
                            $auditItem->implementation()->update(['effectiveness' => $auditItem->effectiveness]);
                        }

                        //Save the final audit report
                        $auditItems = $record->auditItems;
                        $reportTemplate = "reports.audit";
                        if ($record->audit_type == "implementations")
                            $reportTemplate = "reports.implementation-report";
                        $filepath = "app/private/audit_reports/AuditReport-{$record->id}.pdf";
                        $pdf = Pdf::loadView($reportTemplate, ['audit' => $record, 'auditItems' => $auditItems]);
                        $pdf->save(storage_path($filepath));

                        $record->update(['status' => WorkflowStatus::COMPLETED]);
                        $livewire->redirectRoute('filament.app.resources.audits.view', $record);
                    }),
            ])
                ->label('Workflow')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
            ,
            ActionGroup::make([
                Action::make('ReportsButton')
                    ->label('Audit Report')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->disabled($record->status == WorkflowStatus::NOTSTARTED)
                    ->action(function (Audit $audit, $livewire) {
                        if ($audit->status == WorkflowStatus::COMPLETED) {
                            $filepath = "app/private/audit_reports/AuditReport-{$this->record->id}.pdf";
                            if (file_exists(storage_path($filepath)) && is_readable(storage_path($filepath))) {
                                return response()->download(storage_path($filepath));
                            } else {
                                return redirect()->back()->with('error', 'The audit report is not available.');
                            }
                        } else {
                            $auditItems = $audit->auditItems;
                            $reportTemplate = "reports.audit";
                            if ($audit->audit_type == "implementations")
                                $reportTemplate = "reports.implementation-report";
                            $pdf = Pdf::loadView($reportTemplate, ['audit' => $audit, 'auditItems' => $auditItems]);
                            return response()->streamDownload(
                                fn() => print($pdf->stream()),
                                "AuditReport-{$audit->id}.pdf");
                        }
                    }
                    )
                ,
            ])
                ->label('Reports')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
            ,
            ActionGroup::make([
                Action::make('OperationsButton')
                    ->label('Import IRL')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->disabled($record->status == WorkflowStatus::NOTSTARTED)
                    ->action(function (Audit $audit, $livewire) {
                        return redirect()->route('filament.app.resources.audits.import-irl', $audit);
                    }
                    )
                ,
            ])
                ->label('Data Operations')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
            ,
        ];
    }

}
