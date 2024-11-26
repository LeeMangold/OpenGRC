<?php

namespace App\Filament\Resources;

use App\Enums\StandardStatus;
use App\Filament\Resources\StandardResource\Pages;
use App\Filament\Resources\StandardResource\RelationManagers;
use App\Models\Standard;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StandardResource extends Resource
{
    protected static ?string $model = Standard::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Foundations';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                TextInput::make('name')
                    ->autofocus()
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Critical Security Controls, Version 8')
                    ->hint('Enter the name of the standard.')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Need some more information?'),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. CSCv8')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Give the standard a unique ID or Code.'),
                TextInput::make('authority')
                    ->required()
                    ->maxLength(255)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter the name of the organization that maintains the standard.')
                    ->placeholder('e.g. Center for Internet Security'),
                Select::make('status')
                    ->default(StandardStatus::DRAFT)
                    ->required()
                    ->enum(StandardStatus::class)
                    ->options(StandardStatus::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the relevance of this standard to your organization.')
                    ->native(false),
                TextInput::make('reference_url')
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->url()
                    ->placeholder('e.g. https://www.cisecurity.org/controls/')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter the URL of the offical standard document.'),
                RichEditor::make('description')
                    ->columnSpanFull()
                    ->maxLength(65535)
                    ->required()
                    ->hint('Describe the purpose and scope of the standard.')
                    ->placeholder('Description'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap(true),
                Tables\Columns\TextColumn::make('description')
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->wrap(true)
                    ->limit(250),
                Tables\Columns\TextColumn::make('authority')
                    ->searchable()
                    ->sortable()
                    ->wrap(true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(StandardStatus::class)
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('authority')
                    ->options(Standard::pluck('authority', 'authority')->toArray())
                    ->label('Authority'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            StandardResource\Widgets\StandardsHeader::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ControlsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStandards::route('/'),
            'create' => Pages\CreateStandard::route('/create'),
            'view' => Pages\ViewStandard::route('/{record}'),
            'edit' => Pages\EditStandard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    //This is the view page for a single Standard record
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Standard Details')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('code'),
                        TextEntry::make('authority'),
                        TextEntry::make('status'),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->html(),
                    ]),
            ]);
    }

    /**
     * @param  Standard  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->code.'-'.$record->name;
    }

    /**
     * @param  Standard  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return StandardResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Standard  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Standard' => $record->name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'description', 'authority'];
    }
}
