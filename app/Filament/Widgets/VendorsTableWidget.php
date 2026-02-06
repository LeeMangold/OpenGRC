<?php

namespace App\Filament\Widgets;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource;
use App\Models\User;
use App\Models\Vendor;
use App\Services\VendorAssessmentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class VendorsTableWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Vendor::query()->with(['vendorManager' => fn ($q) => $q->withTrashed()]))
            ->heading(__('Vendors'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
                    ->formatStateUsing(function (Vendor $record): string {
                        /** @var \App\Models\User|null $vendorManager */
                        $vendorManager = $record->vendorManager;

                        return $vendorManager
                            ? ($vendorManager->trashed() ? $vendorManager->name.' (Deactivated)' : $vendorManager->name)
                            : '';
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor()),
                TextColumn::make('risk_rating')
                    ->label(__('Organizational Impact'))
                    ->badge()
                    ->color(fn ($record) => $record->risk_rating->getColor()),
                TextColumn::make('risk_score')
                    ->label(__('Assessed Risk'))
                    ->badge()
                    ->default(__('Not Assessed'))
                    ->color(fn ($state): string => $state === __('Not Assessed') || $state === null
                        ? 'gray'
                        : VendorRiskRating::fromScore((int) $state)->getColor())
                    ->formatStateUsing(fn ($state): string => $state === __('Not Assessed') || $state === null
                        ? __('Not Assessed')
                        : VendorRiskRating::fromScore((int) $state)->getLabel())
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                SelectFilter::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                SelectFilter::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->options(User::optionsWithDeactivated()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record])),
                    Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Vendor $record): string => VendorResource::getUrl('edit', ['record' => $record])),
                    Action::make('assess_risk')
                        ->label(__('Assess Risk'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('primary')
                        ->schema(VendorAssessmentService::getAssessRiskFormSchema())
                        ->action(fn (Vendor $record, array $data) => VendorAssessmentService::handleAssessRisk($record, $data)),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No vendors'))
            ->emptyStateDescription(__('Get started by creating your first vendor.'))
            ->defaultSort('name', 'asc')
            ->recordUrl(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record]));
    }
}
