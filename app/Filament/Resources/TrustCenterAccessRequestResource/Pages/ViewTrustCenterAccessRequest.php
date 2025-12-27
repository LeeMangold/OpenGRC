<?php

namespace App\Filament\Resources\TrustCenterAccessRequestResource\Pages;

use App\Filament\Resources\TrustCenterAccessRequestResource;
use App\Mail\TrustCenterAccessApprovedMail;
use App\Mail\TrustCenterAccessRejectedMail;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewTrustCenterAccessRequest extends ViewRecord
{
    protected static string $resource = TrustCenterAccessRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('approve')
                ->label(__('Approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('review_notes')
                        ->label(__('Notes (Optional)'))
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->approve(auth()->user(), $data['review_notes'] ?? null);

                    try {
                        Mail::to($this->record->requester_email)->send(new TrustCenterAccessApprovedMail($this->record));

                        Notification::make()
                            ->title(__('Access Approved'))
                            ->body(__('Access approved and email sent to :email', ['email' => $this->record->requester_email]))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Access Approved'))
                            ->body(__('Access approved but email failed to send: :error', ['error' => $e->getMessage()]))
                            ->warning()
                            ->send();
                    }

                    return redirect(TrustCenterAccessRequestResource::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => $this->record->isPending()),
            Actions\Action::make('reject')
                ->label(__('Reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('review_notes')
                        ->label(__('Reason for Rejection'))
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->reject(auth()->user(), $data['review_notes'] ?? null);

                    try {
                        Mail::to($this->record->requester_email)->send(new TrustCenterAccessRejectedMail($this->record));

                        Notification::make()
                            ->title(__('Access Rejected'))
                            ->body(__('Request rejected and email sent to :email', ['email' => $this->record->requester_email]))
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title(__('Access Rejected'))
                            ->body(__('Request rejected but email failed to send: :error', ['error' => $e->getMessage()]))
                            ->warning()
                            ->send();
                    }

                    return redirect(TrustCenterAccessRequestResource::getUrl('view', ['record' => $this->record]));
                })
                ->visible(fn () => $this->record->isPending()),
            Actions\DeleteAction::make(),
        ];
    }
}
