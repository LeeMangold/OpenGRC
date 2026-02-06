<?php

namespace App\Filament\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Enums\DocumentType;
use App\Filament\Exports\PolicyExporter;
use App\Filament\Resources\PolicyResource\Pages\CreatePolicy;
use App\Filament\Resources\PolicyResource\Pages\EditPolicy;
use App\Filament\Resources\PolicyResource\Pages\ListPolicies;
use App\Filament\Resources\PolicyResource\Pages\ViewPolicy;
use App\Filament\Resources\PolicyResource\Pages\ViewPolicyDetails;
use App\Filament\Resources\PolicyResource\RelationManagers\ControlsRelationManager;
use App\Filament\Resources\PolicyResource\RelationManagers\ExceptionsRelationManager;
use App\Filament\Resources\PolicyResource\RelationManagers\ImplementationsRelationManager;
use App\Filament\Resources\PolicyResource\RelationManagers\RisksRelationManager;
use App\Models\Policy;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Policies';

    protected static string|\UnitEnum|null $navigationGroup = 'Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Core Information Section
                Section::make('Document Information')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->label('Policy Code')
                            ->placeholder('e.g., POL-001')
                            ->helperText('Unique identifier for this policy'),

                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Policy Name')
                            ->placeholder('e.g., Information Security Policy')
                            ->columnSpan(3),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options(DocumentType::class)
                            ->default(DocumentType::Policy)
                            ->required()
                            ->helperText('Type of document (Policy, Procedure, Standard, etc.)'),

                        Select::make('status_id')
                            ->label('Status')
                            ->options(fn () => Taxonomy::where('slug', 'policy-status')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->required()
                            ->helperText('Current status of the policy'),

                        Select::make('scope_id')
                            ->label('Scope')
                            ->options(fn () => Taxonomy::where('slug', 'policy-scope')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->helperText('Organizational scope of this policy'),

                        Select::make('department_id')
                            ->label('Department')
                            ->options(fn () => Taxonomy::where('slug', 'department')->first()?->children()->pluck('name', 'id') ?? collect())
                            ->searchable()
                            ->helperText('Department responsible for this policy'),

                        Select::make('owner_id')
                            ->label('Policy Owner')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('User responsible for this policy'),

                        DatePicker::make('effective_date')
                            ->label('Effective Date')
                            ->helperText('Date when this policy becomes effective'),

                        DatePicker::make('retired_date')
                            ->label('Retired Date')
                            ->helperText('Date when this policy was retired (only for retired/superseded policies)'),
                    ]),

                // Policy Content Section
                Section::make('Policy Content')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('policy_scope')
                            ->label('Policy Scope')
                            ->columnSpanFull()
                            ->helperText('Define the scope and applicability of this policy'),

                        RichEditor::make('purpose')
                            ->label('Purpose')
                            ->columnSpanFull()
                            ->helperText('Explain the purpose and objectives of this policy'),

                        RichEditor::make('body')
                            ->label('Policy Body')
                            ->columnSpanFull()
                            ->helperText('Main content and requirements of the policy'),
                    ]),

                // Document Upload Section
                Section::make('Policy Document')
                    ->columnSpanFull()
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Upload Policy Document')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240)
                            ->directory('policies')
                            ->columnSpanFull()
                            ->helperText('Upload a policy document instead of filling in the fields above (PDF, DOC, DOCX - max 10MB)'),
                    ])
                    ->collapsed()
                    ->description('Optionally upload a policy document file'),

                // Revision History Section
                Section::make('Revision History')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('revision_history')
                            ->label('')
                            ->schema([
                                TextInput::make('version')
                                    ->label('Version')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., 1.0, 2.1'),

                                DatePicker::make('date')
                                    ->label('Date')
                                    ->required(),

                                TextInput::make('author')
                                    ->label('Author')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Author name'),

                                Textarea::make('changes')
                                    ->label('Changes')
                                    ->required()
                                    ->rows(3)
                                    ->placeholder('Describe the changes made in this version')
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add Revision')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['version'] ?? null)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->description('Track version history and changes to this policy'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Code')
                    ->toggleable()
                    ->color('primary'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('document_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state?->getLabel() ?? DocumentType::Other->getLabel())
                    ->color(fn ($state) => $state?->getColor() ?? DocumentType::Other->getColor())
                    ->icon(fn ($state) => $state?->getIcon() ?? DocumentType::Other->getIcon())
                    ->sortable(),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->toggleable()
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'In Review' => 'info',
                        'Awaiting Feedback' => 'warning',
                        'Pending Approval' => 'warning',
                        'Approved' => 'success',
                        'Archived' => 'gray',
                        'Superseded', 'Retired' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('scope.name')
                    ->label('Scope')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->formatStateUsing(fn ($record): string => $record->creator?->displayName() ?? '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updater.name')
                    ->label('Updated By')
                    ->formatStateUsing(fn ($record): string => $record->updater?->displayName() ?? '')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Document Type')
                    ->options(DocumentType::class),

                SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(fn () => Taxonomy::where('slug', 'policy-status')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('scope_id')
                    ->label('Scope')
                    ->options(fn () => Taxonomy::where('slug', 'policy-scope')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(fn () => Taxonomy::where('slug', 'department')->first()?->children()->pluck('name', 'id') ?? collect())
                    ->searchable(),

                SelectFilter::make('owner_id')
                    ->label('Policy Owner')
                    ->relationship('owner', 'name')
                    ->searchable(),

                TrashedFilter::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(PolicyExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('updateDocumentType')
                        ->label('Update Document Type')
                        ->icon('heroicon-o-document-text')
                        ->form([
                            Select::make('document_type')
                                ->label('Document Type')
                                ->options(DocumentType::class)
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['document_type' => $data['document_type']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('updateStatus')
                        ->label('Update Status')
                        ->icon('heroicon-o-flag')
                        ->form([
                            Select::make('status_id')
                                ->label('Status')
                                ->options(fn () => Taxonomy::where('slug', 'policy-status')->first()?->children()->pluck('name', 'id') ?? collect())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['status_id' => $data['status_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('updateDepartment')
                        ->label('Update Department')
                        ->icon('heroicon-o-building-office')
                        ->form([
                            Select::make('department_id')
                                ->label('Department')
                                ->options(fn () => Taxonomy::where('slug', 'department')->first()?->children()->pluck('name', 'id') ?? collect())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['department_id' => $data['department_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('updateOwner')
                        ->label('Update Owner')
                        ->icon('heroicon-o-user')
                        ->form([
                            Select::make('owner_id')
                                ->label('Policy Owner')
                                ->relationship('owner', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['owner_id' => $data['owner_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('updateScope')
                        ->label('Update Scope')
                        ->icon('heroicon-o-globe-alt')
                        ->form([
                            Select::make('scope_id')
                                ->label('Scope')
                                ->options(fn () => Taxonomy::where('slug', 'policy-scope')->first()?->children()->pluck('name', 'id') ?? collect())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $records->each->update(['scope_id' => $data['scope_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),

                    ExportBulkAction::make()
                        ->exporter(PolicyExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),

            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Header Section
                Section::make('Policy Overview')
                    ->columnSpanFull()
                    ->schema([
                        Flex::make([
                            Grid::make(2)
                                ->schema([
                                    Group::make([
                                        TextEntry::make('code')
                                            ->label('Policy Code')
                                            ->badge()
                                            ->color('primary')
                                            ->size(TextSize::Large),

                                        TextEntry::make('name')
                                            ->label('Policy Name')
                                            ->size(TextSize::Large)
                                            ->weight('bold'),

                                        TextEntry::make('document_type')
                                            ->label('Document Type')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => $state?->getLabel() ?? DocumentType::Other->getLabel())
                                            ->color(fn ($state) => $state?->getColor() ?? DocumentType::Other->getColor())
                                            ->icon(fn ($state) => $state?->getIcon() ?? DocumentType::Other->getIcon()),
                                    ]),

                                    Group::make([
                                        TextEntry::make('status.name')
                                            ->label('Status')
                                            ->badge()
                                            ->color(fn (string $state): string => match ($state) {
                                                'Draft' => 'gray',
                                                'In Review' => 'info',
                                                'Awaiting Feedback' => 'warning',
                                                'Pending Approval' => 'warning',
                                                'Approved' => 'success',
                                                'Archived' => 'gray',
                                                'Superseded', 'Retired' => 'danger',
                                                default => 'gray',
                                            }),

                                        TextEntry::make('scope.name')
                                            ->label('Scope')
                                            ->badge()
                                            ->color('info')
                                            ->placeholder('Not specified'),
                                    ]),
                                ]),
                        ])->from('md'),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('department.name')
                                    ->label('Department')
                                    ->icon('heroicon-o-building-office')
                                    ->placeholder('Not assigned'),

                                TextEntry::make('document_path')
                                    ->label('Document')
                                    ->formatStateUsing(fn ($state) => $state ? 'Document Uploaded' : 'No Document')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray')
                                    ->icon(fn ($state) => $state ? 'heroicon-o-document-check' : 'heroicon-o-document'),
                            ]),
                    ])
                    ->collapsible(),

                // Policy Content
                Section::make('Policy Content')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('policy_scope')
                            ->label('Policy Scope')
                            ->html()
                            ->placeholder('No scope defined')
                            ->columnSpanFull(),

                        TextEntry::make('purpose')
                            ->label('Purpose')
                            ->html()
                            ->placeholder('No purpose defined')
                            ->columnSpanFull(),

                        TextEntry::make('body')
                            ->label('Policy Body')
                            ->html()
                            ->placeholder('No body content')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                // Revision History
                Section::make('Revision History')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('revision_history')
                            ->label('')
                            ->schema([
                                TextEntry::make('version')
                                    ->label('Version')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('date')
                                    ->label('Date')
                                    ->date('n/j/Y'),

                                TextEntry::make('author')
                                    ->label('Author')
                                    ->icon('heroicon-o-user'),

                                TextEntry::make('changes')
                                    ->html()
                                    ->label('Changes'),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => $record->revision_history && count($record->revision_history) > 0),

                // Metadata
                Section::make('Metadata')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created By')
                            ->formatStateUsing(fn ($record): string => $record->creator?->displayName() ?? '')
                            ->icon('heroicon-o-user'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M d, Y H:i'),

                        TextEntry::make('updater.name')
                            ->label('Last Updated By')
                            ->formatStateUsing(fn ($record): string => $record->updater?->displayName() ?? '')
                            ->icon('heroicon-o-user'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('M d, Y H:i')
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ControlsRelationManager::class,
            ImplementationsRelationManager::class,
            RisksRelationManager::class,
            ExceptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolicies::route('/'),
            'create' => CreatePolicy::route('/create'),
            'view' => ViewPolicy::route('/{record}'),
            'view-details' => ViewPolicyDetails::route('/{record}/details'),
            'edit' => EditPolicy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['status', 'scope', 'department', 'owner' => fn ($q) => $q->withTrashed(), 'creator' => fn ($q) => $q->withTrashed(), 'updater' => fn ($q) => $q->withTrashed()]);
    }

    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->name.' ('.$record->code.')';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'policy_scope', 'purpose', 'body'];
    }
}
