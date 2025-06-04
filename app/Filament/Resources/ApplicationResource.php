<?php

namespace App\Filament\Resources;

use App\Enums\ApplicationType;
use App\Enums\ApplicationStatus;
use App\Filament\Resources\ApplicationResource\Pages;
use App\Models\Application;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-window';

    public static function getNavigationLabel(): string
    {
        return __('Applications');
    }

    public static function getNavigationGroup(): string
    {
        return __('Entities');
    }

    public static function getModelLabel(): string
    {
        return __('Application');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Applications');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('owner_id')
                    ->label(__('Owner'))
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label(__('Type'))
                    ->enum(ApplicationType::class)
                    ->options(collect(ApplicationType::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(65535),
                Forms\Components\Select::make('status')
                    ->label(__('Status'))
                    ->enum(ApplicationStatus::class)
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->label(__('URL'))
                    ->maxLength(512),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535),
                Forms\Components\Select::make('vendor_id')
                    ->label(__('Vendor'))
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\FileUpload::make('logo')
                    ->label(__('Logo'))
                    ->disk(config('filesystems.default'))
                    ->directory('application-logos')
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
                Tables\Columns\TextColumn::make('owner.name')->label(__('Owner'))->searchable(),
                Tables\Columns\TextColumn::make('type')->label(__('Type'))->badge()->color(fn($record) => $record->type->getColor()),
                Tables\Columns\TextColumn::make('vendor.name')->label(__('Vendor'))->searchable(),
                Tables\Columns\TextColumn::make('status')->label(__('Status'))->badge()->color(fn($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('url')->label(__('URL'))->url(fn($record) => $record->url, true),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created'))->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->filters([
                
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
} 