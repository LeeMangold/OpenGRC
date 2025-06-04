<?php

namespace App\Filament\Resources;

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use App\Filament\Resources\VendorResource\Pages;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\VendorResource\RelationManagers\ApplicationsRelationManager;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    public static function getNavigationLabel(): string
    {
        return __('Vendors');
    }

    public static function getNavigationGroup(): string
    {
        return __('Entities');
    }

    public static function getModelLabel(): string
    {
        return __('Vendor');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Vendors');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(65535),
                Forms\Components\TextInput::make('url')
                    ->label(__('URL'))
                    ->maxLength(512),
                Forms\Components\Select::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->relationship('vendorManager', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->enum(VendorStatus::class)
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Forms\Components\Select::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->enum(VendorRiskRating::class)
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535),
                Forms\Components\FileUpload::make('logo')
                    ->label(__('Logo'))
                    ->disk(config('filesystems.default'))
                    ->directory('vendor-logos')
                    ->storeFileNamesIn('logo')                    
                    ->visibility('private')                    
                    ->maxSize(1024) // 1MB
                    ->deletable()                    
                    ->deleteUploadedFileUsing(function ($state) {
                        if ($state) {
                            \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->delete($state);
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(__('Name'))->searchable(),
                Tables\Columns\TextColumn::make('vendorManager.name')->label(__('Vendor Manager'))->searchable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge()->color(fn($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('risk_rating')->label(__('Risk Rating'))->badge()->color(fn($record) => $record->risk_rating->getColor()),
                Tables\Columns\TextColumn::make('url')->label(__('URL'))->url(fn($record) => $record->url, true),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(VendorStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->options(collect(VendorRiskRating::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('vendor_manager_id')
                    ->label(__('Vendor Manager'))
                    ->options(User::all()->pluck('name', 'id')),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ApplicationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVendors::route('/'),
            'create' => Pages\CreateVendor::route('/create'),
            'view' => Pages\ViewVendor::route('/{record}'),
            'edit' => Pages\EditVendor::route('/{record}/edit'),
        ];
    }
} 