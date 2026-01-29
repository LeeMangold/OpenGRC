<?php

namespace App\Filament\Resources;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Filament\Exports\ApplicationExporter;
use App\Filament\Resources\ApplicationResource\Pages\CreateApplication;
use App\Filament\Resources\ApplicationResource\Pages\EditApplication;
use App\Filament\Resources\ApplicationResource\Pages\ListApplications;
use App\Filament\Resources\ApplicationResource\Pages\ViewApplication;
use App\Filament\Resources\ApplicationResource\RelationManagers\ImplementationsRelationManager;
use App\Models\Application;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Select::make('owner_id')
                    ->label(__('Owner'))
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('type')
                    ->label(__('Type'))
                    ->enum(ApplicationType::class)
                    ->options(collect(ApplicationType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(65535),
                Select::make('status')
                    ->label(__('Status'))
                    ->enum(ApplicationStatus::class)
                    ->options(collect(ApplicationStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                    ->required(),
                TextInput::make('url')
                    ->label(__('URL'))
                    ->maxLength(512),
                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->maxLength(65535),
                Select::make('vendor_id')
                    ->label(__('Vendor'))
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                FileUpload::make('logo')
                    ->label(__('Logo'))
                    ->disk(config('filesystems.default'))
                    ->directory('application-logos')
                    ->storeFileNamesIn('logo')
                    ->visibility('private')
                    ->maxSize(1024) // 1MB
                    ->deletable()
                    ->deleteUploadedFileUsing(function ($state) {
                        if ($state) {
                            Storage::disk(config('filesystems.default'))->delete($state);
                        }
                    }),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('Name')),
                TextEntry::make('owner.name')
                    ->label(__('Owner')),
                TextEntry::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn ($record) => $record->type->getColor()),
                TextEntry::make('description')
                    ->label(__('Description')),
                TextEntry::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor()),
                TextEntry::make('url')
                    ->label(__('URL'))
                    ->url(fn ($record) => $record->url, true),
                TextEntry::make('notes')
                    ->label(__('Notes')),
                TextEntry::make('vendor.name')
                    ->label(__('Vendor')),
                TextEntry::make('created_at')
                    ->label(__('Created'))
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->label(__('Updated'))
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable(),
                TextColumn::make('owner.name')->label(__('Owner'))->searchable(),
                TextColumn::make('type')->label(__('Type'))->badge()->color(fn ($record) => $record->type->getColor()),
                TextColumn::make('vendor.name')->label(__('Vendor'))->searchable(),
                TextColumn::make('status')->label(__('Status'))->badge()->color(fn ($record) => $record->status->getColor()),
                TextColumn::make('url')->label(__('URL'))->url(fn ($record) => $record->url, true),
                TextColumn::make('created_at')->label(__('Created'))->dateTime()->sortable(),
                TextColumn::make('updated_at')->label(__('Updated'))->dateTime()->sortable(),
            ])
            ->filters([

            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ApplicationExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ApplicationExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImplementationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplications::route('/'),
            'create' => CreateApplication::route('/create'),
            'view' => ViewApplication::route('/{record}'),
            'edit' => EditApplication::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['owner', 'vendor']);
    }

    /**
     * @param  Application  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    /**
     * @param  Application  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ApplicationResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Application  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Application $record */
        return [
            'Type' => $record->type?->getLabel() ?? 'Unknown',
            'Vendor' => $record->vendor?->getAttribute('name') ?? 'None',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'url', 'notes'];
    }
}
