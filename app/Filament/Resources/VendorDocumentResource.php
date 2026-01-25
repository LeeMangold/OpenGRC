<?php

namespace App\Filament\Resources;

use App\Enums\VendorDocumentStatus;
use App\Enums\VendorDocumentType;
use App\Filament\Resources\VendorDocumentResource\Pages\CreateVendorDocument;
use App\Filament\Resources\VendorDocumentResource\Pages\EditVendorDocument;
use App\Filament\Resources\VendorDocumentResource\Pages\ListVendorDocuments;
use App\Filament\Resources\VendorDocumentResource\Pages\ViewVendorDocument;
use App\Models\VendorDocument;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class VendorDocumentResource extends Resource
{
    protected static ?string $model = VendorDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Vendor Management';

    protected static ?string $navigationLabel = 'Vendor Documents';

    protected static ?int $navigationSort = 3;

    // Hide from navigation - access via Vendor relation manager
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options(VendorDocumentType::class)
                            ->required()
                            ->native(false),

                        TextInput::make('name')
                            ->label('Document Name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000),

                        FileUpload::make('file_path')
                            ->label('Document File')
                            ->required()
                            ->disk(config('filesystems.default'))
                            ->directory('vendor-documents')
                            ->visibility('private')
                            ->maxSize(20480)
                            ->storeFileNamesIn('file_name'),

                        Select::make('status')
                            ->label('Status')
                            ->options(VendorDocumentStatus::class)
                            ->required()
                            ->native(false)
                            ->default(VendorDocumentStatus::PENDING),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('issue_date')
                            ->label('Issue Date')
                            ->native(false),

                        DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('expiration_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn (VendorDocument $record) => match (true) {
                        $record->isExpired() => 'danger',
                        $record->isExpiringSoon() => 'warning',
                        default => null,
                    }),

                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vendor_id')
                    ->label('Vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('document_type')
                    ->label('Type')
                    ->options(VendorDocumentType::class),

                SelectFilter::make('status')
                    ->options(VendorDocumentStatus::class),

                Filter::make('pending_review')
                    ->label('Pending Review')
                    ->query(fn ($query) => $query->where('status', VendorDocumentStatus::PENDING))
                    ->toggle(),

                Filter::make('expiring_soon')
                    ->label('Expiring Soon (30 days)')
                    ->query(fn ($query) => $query->expiringSoon())
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (VendorDocument $record) {
                        return Storage::disk(config('filesystems.default'))
                            ->download($record->file_path, $record->file_name);
                    }),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Review Notes')
                            ->placeholder('Optional notes about the approval...')
                            ->rows(3),
                    ])
                    ->action(function (VendorDocument $record, array $data) {
                        $record->update([
                            'status' => VendorDocumentStatus::APPROVED,
                            'review_notes' => $data['review_notes'] ?? null,
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Document approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (VendorDocument $record) => in_array($record->status, [
                        VendorDocumentStatus::PENDING,
                        VendorDocumentStatus::UNDER_REVIEW,
                    ])),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('review_notes')
                            ->label('Rejection Reason')
                            ->placeholder('Please explain why this document is being rejected...')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (VendorDocument $record, array $data) {
                        $record->update([
                            'status' => VendorDocumentStatus::REJECTED,
                            'review_notes' => $data['review_notes'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Document rejected')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (VendorDocument $record) => in_array($record->status, [
                        VendorDocumentStatus::PENDING,
                        VendorDocumentStatus::UNDER_REVIEW,
                    ])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('vendor.name')
                            ->label('Vendor'),

                        TextEntry::make('document_type')
                            ->label('Type')
                            ->badge(),

                        TextEntry::make('name')
                            ->label('Name'),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull()
                            ->placeholder('No description'),

                        TextEntry::make('file_name')
                            ->label('File'),

                        TextEntry::make('uploadedBy.name')
                            ->label('Uploaded By')
                            ->placeholder('Unknown'),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('issue_date')
                            ->label('Issue Date')
                            ->date()
                            ->placeholder('Not specified'),

                        TextEntry::make('expiration_date')
                            ->label('Expiration Date')
                            ->date()
                            ->color(fn (VendorDocument $record) => match (true) {
                                $record->isExpired() => 'danger',
                                $record->isExpiringSoon() => 'warning',
                                default => null,
                            })
                            ->placeholder('No expiration'),

                        TextEntry::make('created_at')
                            ->label('Uploaded')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Review Information')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('reviewedBy.name')
                            ->label('Reviewed By')
                            ->placeholder('Not yet reviewed'),

                        TextEntry::make('reviewed_at')
                            ->label('Reviewed At')
                            ->dateTime()
                            ->placeholder('Not yet reviewed'),

                        TextEntry::make('review_notes')
                            ->label('Review Notes')
                            ->columnSpanFull()
                            ->placeholder('No notes'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorDocuments::route('/'),
            'create' => CreateVendorDocument::route('/create'),
            'view' => ViewVendorDocument::route('/{record}'),
            'edit' => EditVendorDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', VendorDocumentStatus::PENDING)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
