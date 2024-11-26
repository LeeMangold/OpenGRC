<?php

namespace App\Filament\Resources;

use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\Effectiveness;
use App\Filament\Resources\ControlResource\Pages;
use App\Filament\Resources\ControlResource\RelationManagers;
use App\Models\Control;
use App\Models\Standard;
use Filament\Forms;
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

class ControlResource extends Resource
{
    protected static ?string $model = Control::class;

    protected static ?string $navigationIcon = 'heroicon-o-stop-circle';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationGroup = 'Foundations';

    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('standard_id')
                    ->label('Standard')
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'All controls must belong to a standard. If you dont have a standard to relate this control to, consider creating a new one first.')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a unique code for this control. This code will be used to identify this control in the system.')
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a title for this control.')
                    ->maxLength(1024),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a description for this control. This should describe, in detail, the requirements for this this control.')
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('discussion')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Optional: Provide any context or additional information about this control that would help someone determine how to implement it.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('standard.name')
                    ->label('Standard')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('enforcement')
                    ->sortable(),
                //Todo: Make Control Effectiveness Sortable
                Tables\Columns\TextColumn::make('LatestAuditEffectiveness')
                    ->label('Effectiveness')
                    ->badge()
                    ->default(function (Control $record) {
                        return $record->getEffectiveness();
                    }),
                //Todo: Make Last Audit Date Sortable
                Tables\Columns\TextColumn::make('LatestAuditDate')
                    ->label('Assessed')
                    ->default(function (Control $record) {
                        return $record->getEffectivenessDate();
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('standard_id')
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->multiple()
                    ->label('Standard'),
                Tables\Filters\SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class)
                    ->label('Effectiveness'),
                Tables\Filters\SelectFilter::make('type')
                    ->options(ControlType::class)
                    ->label('Type'),
                Tables\Filters\SelectFilter::make('category')
                    ->options(ControlCategory::class)
                    ->label('Category'),
                Tables\Filters\SelectFilter::make('enforcement')
                    ->options(ControlEnforcementCategory::class)
                    ->label('Enforcement'),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImplementationRelationManager::class,
            RelationManagers\AuditItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListControls::route('/'),
            'create' => Pages\CreateControl::route('/create'),
            'view' => Pages\ViewControl::route('/{record}'),
            'edit' => Pages\EditControl::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Control Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('code'),
                        TextEntry::make('effectiveness')
                            ->default(function (Control $record) {
                                return $record->getEffectiveness();
                            }),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('category')->badge(),
                        TextEntry::make('enforcement')->badge(),
                        TextEntry::make('lastAuditDate')
                            ->default(function (Control $record) {
                                return $record->getEffectivenessDate();
                            }),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->html(),
                        TextEntry::make('discussion')
                            ->columnSpanFull()
                            ->html(),
                    ]),
            ]);
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "{$record->code} - {$record->title}";
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ControlResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Control' => $record->title,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'discussion', 'code'];
    }
}
