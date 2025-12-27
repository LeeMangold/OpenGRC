<?php

namespace App\Filament\Resources;

use App\Enums\TrustLevel;
use App\Filament\Resources\TrustCenterDocumentResource\Pages;
use App\Models\TrustCenterDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TrustCenterDocumentResource extends Resource
{
    protected static ?string $model = TrustCenterDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Document Information'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Document Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make(__('Access Control'))
                    ->schema([
                        Forms\Components\Select::make('trust_level')
                            ->label(__('Trust Level'))
                            ->enum(TrustLevel::class)
                            ->options(collect(TrustLevel::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->required()
                            ->default(TrustLevel::PUBLIC->value)
                            ->helperText(__('Public documents are visible to everyone. Protected documents require access approval.')),
                        Forms\Components\Toggle::make('requires_nda')
                            ->label(__('Requires NDA Agreement'))
                            ->helperText(__('If enabled, requesters must agree to the NDA before accessing this document.'))
                            ->default(false)
                            ->visible(fn (Forms\Get $get) => $get('trust_level') === TrustLevel::PROTECTED->value),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->helperText(__('Inactive documents are not shown in the Trust Center.'))
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make(__('Certifications'))
                    ->schema([
                        Forms\Components\Select::make('certifications')
                            ->label(__('Related Certifications'))
                            ->relationship('certifications', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(__('Select the certifications this document relates to.')),
                    ])
                    ->columns(1),

                Forms\Components\Section::make(__('Document File'))
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
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

                Forms\Components\Section::make(__('Validity Period'))
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->label(__('Valid From'))
                            ->native(false),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label(__('Valid Until'))
                            ->native(false)
                            ->helperText(__('Leave empty if the document does not expire.')),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),

                Forms\Components\Section::make(__('Display Order'))
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('name')
                            ->hiddenLabel()
                            ->size(TextEntry\TextEntrySize::Large)
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
                    ->schema([
                        TextEntry::make('description')
                            ->hiddenLabel()
                            ->placeholder(__('No description provided'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->hidden(fn (?TrustCenterDocument $record) => empty($record?->description)),

                Section::make(__('Certifications'))
                    ->schema([
                        TextEntry::make('certifications.name')
                            ->label(__('Related Certifications'))
                            ->badge()
                            ->separator(', '),
                    ])
                    ->collapsible(),

                Section::make(__('File Information'))
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
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('requires_nda')
                    ->label(__('NDA'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label(__('Expires'))
                    ->date()
                    ->placeholder(__('Never'))
                    ->color(fn (?TrustCenterDocument $record): string => $record?->isExpired() ? 'danger' : ($record?->isExpiringSoon() ? 'warning' : 'gray'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label(__('Uploaded By'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trust_level')
                    ->label(__('Trust Level'))
                    ->options(collect(TrustLevel::cases())->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])),
                Tables\Filters\SelectFilter::make('certifications')
                    ->label(__('Certification'))
                    ->relationship('certifications', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('Active'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active only'))
                    ->falseLabel(__('Inactive only')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
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
            'index' => Pages\ListTrustCenterDocuments::route('/'),
            'create' => Pages\CreateTrustCenterDocument::route('/create'),
            'view' => Pages\ViewTrustCenterDocument::route('/{record}'),
            'edit' => Pages\EditTrustCenterDocument::route('/{record}/edit'),
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
