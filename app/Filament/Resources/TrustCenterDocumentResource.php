<?php

namespace App\Filament\Resources;

use App\Enums\TrustLevel;
use App\Filament\Resources\TrustCenterDocumentResource\Pages\CreateTrustCenterDocument;
use App\Filament\Resources\TrustCenterDocumentResource\Pages\EditTrustCenterDocument;
use App\Filament\Resources\TrustCenterDocumentResource\Pages\ListTrustCenterDocuments;
use App\Filament\Resources\TrustCenterDocumentResource\Pages\ViewTrustCenterDocument;
use App\Models\TrustCenterDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrustCenterDocumentResource extends Resource
{
    protected static ?string $model = TrustCenterDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    // Hide from navigation - access via Trust Center Manager
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Trust Center Documents');
    }

    public static function getNavigationGroup(): string
    {
        return __('Trust Center');
    }

    public static function getModelLabel(): string
    {
        return __('Document');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Documents');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Document Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Document Name'))
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label(__('Description'))
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make(__('Access Control'))
                    ->columnSpanFull()
                    ->schema([
                        Select::make('trust_level')
                            ->label(__('Trust Level'))
                            ->enum(TrustLevel::class)
                            ->options(collect(TrustLevel::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->required()
                            ->default(TrustLevel::PUBLIC->value)
                            ->helperText(__('Public documents are visible to everyone. Protected documents require access approval.')),
                        Toggle::make('requires_nda')
                            ->label(__('Requires NDA Agreement'))
                            ->helperText(__('If enabled, requesters must agree to the NDA before accessing this document.'))
                            ->default(false)
                            ->visible(fn (Get $get) => $get('trust_level') === TrustLevel::PROTECTED->value),
                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->helperText(__('Inactive documents are not shown in the Trust Center.'))
                            ->default(true),
                    ])
                    ->columns(3),

                Section::make(__('Certifications'))
                    ->columnSpanFull()
                    ->schema([
                        Select::make('certifications')
                            ->label(__('Related Certifications'))
                            ->relationship('certifications', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('Select the certifications this document relates to.')),
                    ])
                    ->columns(1),

                Section::make(__('Document File'))
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('file_path')
                            ->label(__('Upload Document'))
                            ->disk(setting('storage.driver', 'private'))
                            ->directory('trust-center-documents')
                            ->visibility('private')
                            ->maxSize(20480) // 20MB
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/png',
                                'image/jpeg',
                            ])
                            ->required()
                            ->storeFileNamesIn('file_name')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Section::make(__('Validity Period'))
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('valid_from')
                            ->label(__('Valid From'))
                            ->native(false),
                        DatePicker::make('valid_until')
                            ->label(__('Valid Until'))
                            ->native(false)
                            ->helperText(__('Leave empty if the document does not expire.')),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Section::make(__('Display Order'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('Documents are displayed in ascending order. Lower numbers appear first.')),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->hiddenLabel()
                            ->size(TextSize::Large)
                            ->weight('bold')
                            ->columnSpanFull(),
                        TextEntry::make('trust_level')
                            ->label(__('Trust Level'))
                            ->badge()
                            ->color(fn (TrustCenterDocument $record) => $record->trust_level->getColor()),
                        IconEntry::make('requires_nda')
                            ->label(__('Requires NDA'))
                            ->boolean(),
                        IconEntry::make('is_active')
                            ->label(__('Active'))
                            ->boolean(),
                    ])
                    ->columns(4),

                Section::make(__('Description'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->placeholder(__('No description provided'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->hidden(fn (?TrustCenterDocument $record) => empty($record?->description)),

                Section::make(__('Certifications'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('certifications.name')
                            ->label(__('Related Certifications'))
                            ->badge()
                            ->separator(', '),
                    ])
                    ->collapsible(),

                Section::make(__('File Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('file_name')
                            ->label(__('File Name')),
                        TextEntry::make('file_size')
                            ->label(__('File Size'))
                            ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 2).' KB' : '-'),
                        TextEntry::make('mime_type')
                            ->label(__('File Type')),
                    ])
                    ->columns(3),

                Section::make(__('Validity'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('valid_from')
                            ->label(__('Valid From'))
                            ->date()
                            ->placeholder(__('Not specified')),
                        TextEntry::make('valid_until')
                            ->label(__('Valid Until'))
                            ->date()
                            ->placeholder(__('No expiration')),
                        TextEntry::make('uploadedBy.name')
                            ->label(__('Uploaded By')),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make(__('Metadata'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('Created'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('Updated'))
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trust_level')
                    ->label(__('Trust Level'))
                    ->badge()
                    ->color(fn (TrustCenterDocument $record) => $record->trust_level->getColor()),
                TextColumn::make('certifications.name')
                    ->label(__('Certifications'))
                    ->badge()
                    ->separator(', ')
                    ->wrap()
                    ->toggleable(),
                IconColumn::make('requires_nda')
                    ->label(__('NDA'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                TextColumn::make('valid_until')
                    ->label(__('Expires'))
                    ->date()
                    ->placeholder(__('Never'))
                    ->color(fn (?TrustCenterDocument $record): string => $record?->isExpired() ? 'danger' : ($record?->isExpiringSoon() ? 'warning' : 'gray'))
                    ->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label(__('Uploaded By'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('trust_level')
                    ->label(__('Trust Level'))
                    ->options(collect(TrustLevel::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                SelectFilter::make('certifications')
                    ->label(__('Certification'))
                    ->relationship('certifications', 'name')
                    ->multiple()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label(__('Active'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc');
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
            'index' => ListTrustCenterDocuments::route('/'),
            'create' => CreateTrustCenterDocument::route('/create'),
            'view' => ViewTrustCenterDocument::route('/{record}'),
            'edit' => EditTrustCenterDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
