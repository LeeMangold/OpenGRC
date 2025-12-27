<?php

namespace App\Filament\Widgets\TrustCenter;

use App\Enums\TrustLevel;
use App\Filament\Resources\TrustCenterDocumentResource;
use App\Models\TrustCenterDocument;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;

class TrustCenterDocumentsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Documents';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrustCenterDocument::query()
                    ->orderBy('sort_order', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trust_level')
                    ->label(__('Trust Level'))
                    ->badge()
                    ->color(fn (TrustCenterDocument $record) => $record->trust_level->getColor()),
                Tables\Columns\TextColumn::make('certifications.name')
                    ->label(__('Certifications'))
                    ->badge()
                    ->separator(', ')
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('Expires'))
                    ->date()
                    ->placeholder(__('Never'))
                    ->color(fn (?TrustCenterDocument $record): string => $record?->isExpired() ? 'danger' : ($record?->isExpiringSoon() ? 'warning' : 'gray')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trust_level')
                    ->label(__('Trust Level'))
                    ->options(collect(TrustLevel::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (TrustCenterDocument $record) => TrustCenterDocumentResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->url(fn (TrustCenterDocument $record) => TrustCenterDocumentResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label(__('Activate'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => true]);
                            Notification::make()
                                ->title(__(':count documents activated', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label(__('Deactivate'))
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => false]);
                            Notification::make()
                                ->title(__(':count documents deactivated', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('set_public')
                        ->label(__('Set to Public'))
                        ->icon('heroicon-o-globe-alt')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['trust_level' => TrustLevel::PUBLIC]);
                            Notification::make()
                                ->title(__(':count documents set to public', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('set_protected')
                        ->label(__('Set to Protected'))
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['trust_level' => TrustLevel::PROTECTED]);
                            Notification::make()
                                ->title(__(':count documents set to protected', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('require_nda')
                        ->label(__('Require NDA'))
                        ->icon('heroicon-o-document-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['requires_nda' => true]);
                            Notification::make()
                                ->title(__(':count documents now require NDA', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('remove_nda')
                        ->label(__('Remove NDA Requirement'))
                        ->icon('heroicon-o-document-minus')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['requires_nda' => false]);
                            Notification::make()
                                ->title(__(':count documents no longer require NDA', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label(__('Add Document'))
                    ->icon('heroicon-o-plus')
                    ->url(TrustCenterDocumentResource::getUrl('create'))
                    ->visible(fn () => auth()->check() && auth()->user()->can('Manage Trust Center')),
            ])
            ->emptyStateHeading(__('No Documents'))
            ->emptyStateDescription(__('Upload your first document to get started.'))
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label(__('Add Document'))
                    ->icon('heroicon-o-plus')
                    ->url(TrustCenterDocumentResource::getUrl('create'))
                    ->visible(fn () => auth()->check() && auth()->user()->can('Manage Trust Center')),
            ]);
    }
}
