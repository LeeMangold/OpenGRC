<?php

namespace App\Filament\Widgets;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource;
use App\Models\User;
use App\Models\Vendor;
use App\Services\VendorAssessmentService;
use Filament\Tables;
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
            ->query(Vendor::query()->with(['vendorManager']))
            ->heading(__('Vendors'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('risk_rating')
                    ->label(__('Organizational Impact'))
                    ->badge()
                    ->color(fn ($record) => $record->risk_rating->getColor()),
                Tables\Columns\TextColumn::make('risk_score')
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->options(User::all()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record])),
                    Tables\Actions\Action::make('edit')
                        ->label('Edit')
                        ->icon('heroicon-o-pencil')
                        ->url(fn (Vendor $record): string => VendorResource::getUrl('edit', ['record' => $record])),
                    Tables\Actions\Action::make('assess_risk')
                        ->label(__('Assess Risk'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('primary')
                        ->form(VendorAssessmentService::getAssessRiskFormSchema())
                        ->action(fn (Vendor $record, array $data) => VendorAssessmentService::handleAssessRisk($record, $data)),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('No vendors'))
            ->emptyStateDescription(__('Get started by creating your first vendor.'))
            ->defaultSort('name', 'asc')
            ->recordUrl(fn (Vendor $record): string => VendorResource::getUrl('view', ['record' => $record]));
    }
}
