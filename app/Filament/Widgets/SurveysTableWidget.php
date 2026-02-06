<?php

namespace App\Filament\Widgets;

use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Mail;

class SurveysTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Survey::query()->with(['template', 'assignedTo' => fn ($q) => $q->withTrashed(), 'createdBy' => fn ($q) => $q->withTrashed()]))
            ->heading(__('survey.manager.tabs.surveys'))
            ->columns([
                TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->searchable(['title'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('template.title')
                    ->label(__('survey.survey.table.columns.template'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('respondent_display')
                    ->label(__('survey.survey.table.columns.respondent'))
                    ->getStateUsing(function (Survey $record): string {
                        if ($record->respondent_name) {
                            return $record->respondent_name;
                        }
                        if ($record->respondent_email) {
                            return $record->respondent_email;
                        }
                        /** @var \App\Models\User|null $assignedTo */
                        $assignedTo = $record->assignedTo;
                        if ($assignedTo) {
                            return $assignedTo->trashed()
                                ? $assignedTo->name.' (Deactivated)'
                                : $assignedTo->name;
                        }

                        return '-';
                    }),
                TextColumn::make('status')
                    ->label(__('survey.survey.table.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('progress')
                    ->label(__('survey.survey.table.columns.progress'))
                    ->getStateUsing(fn (Survey $record): string => $record->progress.'%')
                    ->color(fn (Survey $record): string => match (true) {
                        $record->progress === 100 => 'success',
                        $record->progress > 50 => 'warning',
                        $record->progress > 0 => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')
                    ->label(__('survey.survey.table.columns.due_date'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('completed_at')
                    ->label(__('survey.survey.table.columns.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('survey.survey.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SurveyStatus::class)
                    ->label(__('survey.survey.table.filters.status')),
                SelectFilter::make('template_id')
                    ->relationship('template', 'title')
                    ->label(__('survey.survey.table.filters.template')),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record])),
                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Survey $record): string => SurveyResource::getUrl('edit', ['record' => $record])),
                    Action::make('copy_link')
                        ->label(__('survey.survey.actions.copy_link'))
                        ->icon('heroicon-o-clipboard-document')
                        ->color('gray')
                        ->action(fn () => null)
                        ->extraAttributes(fn (Survey $record): array => [
                            'x-data' => '{}',
                            'x-on:click' => "navigator.clipboard.writeText('{$record->getPublicUrl()}'); \$tooltip('Link copied!')",
                        ])
                        ->visible(fn (Survey $record): bool => $record->access_token !== null),
                    Action::make('send_invitation')
                        ->label(__('survey.survey.actions.send_invitation'))
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.send_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.send_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.send_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                $record->update(['status' => SurveyStatus::SENT]);

                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $record->respondent_email]))
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_failed.title'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && $record->status === SurveyStatus::DRAFT),
                    Action::make('resend_invitation')
                        ->label(__('survey.survey.actions.resend_invitation'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.resend_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.resend_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.resend_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $record->respondent_email]))
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_failed.title'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && in_array($record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create Survey')
                    ->icon('heroicon-o-plus')
                    ->url(SurveyResource::getUrl('create')),
            ])
            ->emptyStateHeading(__('survey.survey.table.empty_state.heading'))
            ->emptyStateDescription(__('survey.survey.table.empty_state.description'))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Survey $record): string => SurveyResource::getUrl('view', ['record' => $record]));
    }
}
