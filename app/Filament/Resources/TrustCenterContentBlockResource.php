<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrustCenterContentBlockResource\Pages;
use App\Models\TrustCenterContentBlock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrustCenterContentBlockResource extends Resource
{
    protected static ?string $model = TrustCenterContentBlock::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Block Settings'))
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->label(__('Slug'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->disabled(fn (?TrustCenterContentBlock $record) => $record !== null)
                            ->helperText(__('The slug is used to identify this block and cannot be changed after creation.')),
                        Forms\Components\TextInput::make('icon')
                            ->label(__('Icon'))
                            ->placeholder('heroicon-o-information-circle')
                            ->helperText(__('Heroicon name for display')),
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0),
                        Forms\Components\Toggle::make('is_enabled')
                            ->label(__('Enabled'))
                            ->helperText(__('When enabled, this block will be displayed on the public Trust Center page.'))
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make(__('Content'))
                    ->schema([
                        Forms\Components\RichEditor::make('content')
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->schema([
                        TextEntry::make('title')
                            ->hiddenLabel()
                            ->size(TextEntry\TextEntrySize::Large)
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
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label(__('Enabled'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('Order'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Last Updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label(__('Status'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Enabled'))
                    ->falseLabel(__('Disabled')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
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
            'index' => Pages\ListTrustCenterContentBlocks::route('/'),
            'view' => Pages\ViewTrustCenterContentBlock::route('/{record}'),
            'edit' => Pages\EditTrustCenterContentBlock::route('/{record}/edit'),
        ];
    }
}
