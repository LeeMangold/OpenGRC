<?php

namespace App\Filament\Widgets\TrustCenter;

use App\Filament\Resources\CertificationResource;
use App\Models\Certification;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;

class CertificationsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Certifications';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Certification::query()
                    ->orderBy('sort_order', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Code'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_predefined')
                    ->label(__('Predefined'))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-pencil')
                    ->trueColor('gray')
                    ->falseColor('primary'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('documents_count')
                    ->label(__('Documents'))
                    ->counts('documents')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_predefined')
                    ->label(__('Type'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Predefined'))
                    ->falseLabel(__('Custom')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Certification $record) => CertificationResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->url(fn (Certification $record) => CertificationResource::getUrl('edit', ['record' => $record])),
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
                                ->title(__(':count certifications activated', ['count' => $records->count()]))
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
                                ->title(__(':count certifications deactivated', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            // Only delete non-predefined certifications
                            $deletable = $records->filter(fn ($r) => ! $r->is_predefined);
                            $skipped = $records->count() - $deletable->count();

                            $deletable->each->delete();

                            if ($skipped > 0) {
                                Notification::make()
                                    ->title(__(':deleted deleted, :skipped predefined skipped', [
                                        'deleted' => $deletable->count(),
                                        'skipped' => $skipped,
                                    ]))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title(__(':count certifications deleted', ['count' => $deletable->count()]))
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label(__('Add Custom Certification'))
                    ->icon('heroicon-o-plus')
                    ->url(CertificationResource::getUrl('create'))
                    ->visible(fn () => auth()->check() && auth()->user()->can('Manage Trust Center')),
            ])
            ->emptyStateHeading(__('No Certifications'))
            ->emptyStateDescription(__('Add certifications to categorize your documents.'))
            ->emptyStateIcon('heroicon-o-shield-check');
    }
}
