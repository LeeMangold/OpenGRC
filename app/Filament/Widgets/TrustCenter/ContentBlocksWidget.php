<?php

namespace App\Filament\Widgets\TrustCenter;

use App\Filament\Resources\TrustCenterContentBlockResource;
use App\Filament\Widgets\TrustCenter\Concerns\RequiresTrustCenterManagement;
use App\Models\TrustCenterContentBlock;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;

class ContentBlocksWidget extends BaseWidget
{
    use RequiresTrustCenterManagement;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Content Blocks';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TrustCenterContentBlock::query()
                    ->orderBy('sort_order', 'asc')
            )
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->label(__('Enabled'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Enabled'))
                    ->falseLabel(__('Disabled')),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('Create Content Block'))
                    ->icon('heroicon-o-plus')
                    ->url(TrustCenterContentBlockResource::getUrl('create')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (TrustCenterContentBlock $record) => TrustCenterContentBlockResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->url(fn (TrustCenterContentBlock $record) => TrustCenterContentBlockResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->hidden(fn (TrustCenterContentBlock $record) => $record->slug === 'overview'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enable')
                        ->label(__('Enable'))
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_enabled' => true]);
                            Notification::make()
                                ->title(__(':count content blocks enabled', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('disable')
                        ->label(__('Disable'))
                        ->icon('heroicon-o-eye-slash')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each->update(['is_enabled' => false]);
                            Notification::make()
                                ->title(__(':count content blocks disabled', ['count' => $records->count()]))
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            // Exclude the overview block from deletion
                            $deletableRecords = $records->filter(fn ($record) => $record->slug !== 'overview');
                            $deletableRecords->each->delete();

                            $deletedCount = $deletableRecords->count();
                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->title(__(':count content blocks deleted', ['count' => $deletedCount]))
                                    ->success()
                                    ->send();
                            }

                            if ($records->count() > $deletedCount) {
                                Notification::make()
                                    ->title(__('The Overview block cannot be deleted'))
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading(__('No Content Blocks'))
            ->emptyStateDescription(__('Content blocks let you customize the Trust Center page. Click the button above to create your first content block.'))
            ->emptyStateIcon('heroicon-o-squares-2x2')
            ->reorderable('sort_order');
    }
}
