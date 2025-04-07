<?php

namespace App\Filament\Resources\AuditResource\RelationManagers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public function form(Form $form): Form
    {

        return $form
            ->schema([
                TextArea::make('description')
                    ->label('Description')
                    ->columnSpanFull()
                    ->required(),
                FileUpload::make('file_path')
                    ->downloadable(true)
                    ->columnSpanFull()
                    ->label('File')
                    ->required()
                    ->multiple()
                    ->disk('private')
                    ->directory('attachments')
                    ->storeFileNamesIn('attachment_file_names'),
                DateTimePicker::make('uploaded_at')
                    ->label('Uploaded At')
                    ->default(now())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')->label('File Name'),
                Tables\Columns\TextColumn::make('description')->html()->limit(100)->wrap(),
                Tables\Columns\TextColumn::make('created_at')->label('Uploaded At'),
                Tables\Columns\TextColumn::make('uploaded_by')
                    ->label('Uploaded By')
                    ->getStateUsing(function ($record) {
                        $user = User::find($record->uploaded_by);

                        return $user ? $user->name : 'Unknown';
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('DownloadDraftReport')
                        ->label('Download Draft Report')
                        ->icon('heroicon-o-document')
                        ->action(function ($record) {
                            $audit = $this->getOwnerRecord();
                            $auditItems = $audit->auditItems;
                            $reportTemplate = 'reports.audit';
                            if ($audit->audit_type == 'implementations') {
                                $reportTemplate = 'reports.implementation-report';
                            }
                            $pdf = Pdf::loadView($reportTemplate, ['audit' => $audit, 'auditItems' => $auditItems]);

                            return response()->streamDownload(
                                fn () => print ($pdf->stream()),
                                "DRAFT-AuditReport-{$audit->id}.pdf");
                        }),
                    // Button to download final report
                    Tables\Actions\Action::make('DownloadReport')
                        ->label('Download Final Report')
                        ->icon('heroicon-o-document')
                        ->action(function ($record) {
                            $audit = $this->getOwnerRecord();
                            $filepath = "app/private/audit_reports/AuditReport-{$audit->id}.pdf";
                            if (file_exists(storage_path($filepath)) && is_readable(storage_path($filepath))) {
                                return response()->download(storage_path($filepath));
                            } else {
                                // If the final audit report is not available, show an error message
                                return Notification::make()
                                    ->title('Error')
                                    ->body('The final audit report is not available until the audit has been completed.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                ])
                    ->label('Report Downloads'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
            ]);
    }
}
