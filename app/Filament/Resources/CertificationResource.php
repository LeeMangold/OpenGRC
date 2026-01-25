<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificationResource\Pages\CreateCertification;
use App\Filament\Resources\CertificationResource\Pages\EditCertification;
use App\Filament\Resources\CertificationResource\Pages\ListCertifications;
use App\Filament\Resources\CertificationResource\Pages\ViewCertification;
use App\Models\Certification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CertificationResource extends Resource
{
    protected static ?string $model = Certification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    // Hide from navigation - access via Trust Center Manager
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Certifications');
    }

    public static function getNavigationGroup(): string
    {
        return __('Trust Center');
    }

    public static function getModelLabel(): string
    {
        return __('Certification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Certifications');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Certification Information'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Certification Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label(__('Code'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->helperText(__('A unique identifier for this certification (e.g., soc2-type2, iso27001)')),
                        Textarea::make('description')
                            ->label(__('Description'))
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(__('Display Settings'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('icon')
                            ->label(__('Icon'))
                            ->placeholder('heroicon-o-shield-check')
                            ->helperText(__('Heroicon name for display (e.g., heroicon-o-shield-check)')),
                        TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0)
                            ->helperText(__('Certifications are displayed in ascending order.')),
                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->helperText(__('Inactive certifications are not shown in the Trust Center.'))
                            ->default(true),
                    ])
                    ->columns(3),
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
                            ->weight('bold'),
                        TextEntry::make('code')
                            ->label(__('Code'))
                            ->badge()
                            ->color('gray'),
                        IconEntry::make('is_predefined')
                            ->label(__('Predefined'))
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
                    ->hidden(fn (?Certification $record) => empty($record?->description)),

                Section::make(__('Documents'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('documents_count')
                            ->label(__('Related Documents'))
                            ->state(fn (Certification $record) => $record->documents()->count()),
                    ])
                    ->collapsible(),
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
                TextColumn::make('code')
                    ->label(__('Code'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                IconColumn::make('is_predefined')
                    ->label(__('Predefined'))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-pencil')
                    ->trueColor('gray')
                    ->falseColor('primary'),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                TextColumn::make('documents_count')
                    ->label(__('Documents'))
                    ->counts('documents')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_predefined')
                    ->label(__('Type'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Predefined'))
                    ->falseLabel(__('Custom')),
                TernaryFilter::make('is_active')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Certification $record) => $record->is_predefined),
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
            'index' => ListCertifications::route('/'),
            'create' => CreateCertification::route('/create'),
            'view' => ViewCertification::route('/{record}'),
            'edit' => EditCertification::route('/{record}/edit'),
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
