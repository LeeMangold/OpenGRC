<?php

namespace App\Filament\Widgets\TrustCenter;

use App\Enums\AccessRequestStatus;
use App\Filament\Resources\TrustCenterAccessRequestResource;
use App\Mail\TrustCenterAccessApprovedMail;
use App\Mail\TrustCenterAccessRejectedMail;
use App\Models\TrustCenterAccessRequest;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;

class PendingAccessRequestsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Access Requests';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrustCenterAccessRequest::query()
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('requester_name')
                    ->label(__('Name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('requester_company')
                    ->label(__('Company'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('requester_email')
                    ->label(__('Email')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (TrustCenterAccessRequest $record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label(__('Documents'))
                    ->counts('documents')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(AccessRequestStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (TrustCenterAccessRequest $record) => TrustCenterAccessRequestResource::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->status === AccessRequestStatus::PENDING)
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label(__('Notes (Optional)'))
                            ->rows(2),
                    ])
                    ->action(function (TrustCenterAccessRequest $record, array $data) {
                        $record->approve(auth()->user(), $data['review_notes'] ?? null);

                        try {
                            Mail::send(new TrustCenterAccessApprovedMail($record));
                            Notification::make()
                                ->title(__('Access Approved'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Approved but email failed'))
                                ->warning()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TrustCenterAccessRequest $record) => $record->status === AccessRequestStatus::PENDING)
                    ->form([
                        Forms\Components\Textarea::make('review_notes')
                            ->label(__('Reason'))
                            ->rows(2),
                    ])
                    ->action(function (TrustCenterAccessRequest $record, array $data) {
                        $record->reject(auth()->user(), $data['review_notes'] ?? null);

                        try {
                            Mail::send(new TrustCenterAccessRejectedMail($record));
                            Notification::make()
                                ->title(__('Request Rejected'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title(__('Rejected but email failed'))
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label(__('Approve Selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('Approve Selected Requests'))
                        ->modalDescription(__('Are you sure you want to approve the selected requests? Approval emails will be sent to each requester.'))
                        ->action(function (Collection $records) {
                            $approved = 0;
                            $skipped = 0;
                            $emailFailed = 0;

                            foreach ($records as $record) {
                                if ($record->status !== AccessRequestStatus::PENDING) {
                                    $skipped++;

                                    continue;
                                }

                                $record->approve(auth()->user());
                                $approved++;

                                try {
                                    Mail::send(new TrustCenterAccessApprovedMail($record));
                                } catch (\Exception $e) {
                                    $emailFailed++;
                                }
                            }

                            $message = __(':approved approved', ['approved' => $approved]);
                            if ($skipped > 0) {
                                $message .= __(', :skipped skipped (not pending)', ['skipped' => $skipped]);
                            }
                            if ($emailFailed > 0) {
                                $message .= __(', :failed emails failed', ['failed' => $emailFailed]);
                            }

                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label(__('Reject Selected'))
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('Reject Selected Requests'))
                        ->modalDescription(__('Are you sure you want to reject the selected requests? Rejection emails will be sent to each requester.'))
                        ->form([
                            Forms\Components\Textarea::make('review_notes')
                                ->label(__('Rejection Reason (applied to all)'))
                                ->rows(2),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $rejected = 0;
                            $skipped = 0;
                            $emailFailed = 0;

                            foreach ($records as $record) {
                                if ($record->status !== AccessRequestStatus::PENDING) {
                                    $skipped++;

                                    continue;
                                }

                                $record->reject(auth()->user(), $data['review_notes'] ?? null);
                                $rejected++;

                                try {
                                    Mail::send(new TrustCenterAccessRejectedMail($record));
                                } catch (\Exception $e) {
                                    $emailFailed++;
                                }
                            }

                            $message = __(':rejected rejected', ['rejected' => $rejected]);
                            if ($skipped > 0) {
                                $message .= __(', :skipped skipped (not pending)', ['skipped' => $skipped]);
                            }
                            if ($emailFailed > 0) {
                                $message .= __(', :failed emails failed', ['failed' => $emailFailed]);
                            }

                            Notification::make()
                                ->title($message)
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No Access Requests'))
            ->emptyStateDescription(__('Access requests will appear here when third parties request access to protected documents.'))
            ->emptyStateIcon('heroicon-o-inbox');
    }
}
