<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrustCenterContentBlockResource\Pages\CreateTrustCenterContentBlock;
use App\Filament\Resources\TrustCenterContentBlockResource\Pages\EditTrustCenterContentBlock;
use App\Filament\Resources\TrustCenterContentBlockResource\Pages\ListTrustCenterContentBlocks;
use App\Filament\Resources\TrustCenterContentBlockResource\Pages\ViewTrustCenterContentBlock;
use App\Models\TrustCenterContentBlock;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Table;

class TrustCenterContentBlockResource extends Resource
{
    protected static ?string $model = TrustCenterContentBlock::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    // Hide from navigation - access via Trust Center Manager
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Content Blocks');
    }

    public static function getNavigationGroup(): string
    {
        return __('Trust Center');
    }

    public static function getModelLabel(): string
    {
        return __('Content Block');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Content Blocks');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Block Settings'))
                    ->schema([
                        TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->disabled(fn (?TrustCenterContentBlock $record) => $record !== null)
                            ->helperText(__('The slug is used to identify this block and cannot be changed after creation.')),
                        TextInput::make('icon')
                            ->label(__('Icon'))
                            ->placeholder('heroicon-o-information-circle')
                            ->helperText(__('Heroicon name for display')),
                        TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_enabled')
                            ->label(__('Enabled'))
                            ->helperText(__('When enabled, this block will be displayed on the public Trust Center page.'))
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make(__('Content'))
                    ->schema([
                        RichEditor::make('content')
                            ->label(__('Content'))
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'orderedList',
                                'bulletList',
                                'h2',
                                'h3',
                                'blockquote',
                                'redo',
                                'undo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextEntry::make('title')
                            ->hiddenLabel()
                            ->size(TextSize::Large)
                            ->weight('bold'),
                        TextEntry::make('slug')
                            ->label(__('Slug'))
                            ->badge()
                            ->color('gray'),
                        IconEntry::make('is_enabled')
                            ->label(__('Enabled'))
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make(__('Content Preview'))
                    ->schema([
                        TextEntry::make('content')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                IconColumn::make('is_enabled')
                    ->label(__('Enabled'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_enabled')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Enabled'))
                    ->falseLabel(__('Disabled')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (TrustCenterContentBlock $record) => $record->slug === 'overview'),
            ])
            ->toolbarActions([])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order');
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
            'index' => ListTrustCenterContentBlocks::route('/'),
            'create' => CreateTrustCenterContentBlock::route('/create'),
            'view' => ViewTrustCenterContentBlock::route('/{record}'),
            'edit' => EditTrustCenterContentBlock::route('/{record}/edit'),
        ];
    }
}
