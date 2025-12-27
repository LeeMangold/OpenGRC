<?php

namespace App\Filament\Widgets\TrustCenter;

use App\Filament\Resources\TrustCenterContentBlockResource;
use App\Models\TrustCenterContentBlock;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Collection;

class ContentBlocksWidget extends BaseWidget
{
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
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label(__('Enabled'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Enabled'))
                    ->falseLabel(__('Disabled')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (TrustCenterContentBlock $record) => TrustCenterContentBlockResource::getUrl('view', ['record' => $record])),
                Tables\Actions\EditAction::make()
                    ->url(fn (TrustCenterContentBlock $record) => TrustCenterContentBlockResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable')
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
                    Tables\Actions\BulkAction::make('disable')
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
                ]),
            ])
            ->emptyStateHeading(__('No Content Blocks'))
            ->emptyStateDescription(__('Content blocks let you customize the Trust Center page.'))
            ->emptyStateIcon('heroicon-o-squares-2x2')
            ->reorderable('sort_order');
    }
}
