<?php

namespace App\Filament\Resources;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Enums\Applicability;
use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\Effectiveness;
use App\Filament\Resources\ControlResource\Pages;
use App\Filament\Resources\ControlResource\RelationManagers;
use App\Models\Control;
use App\Models\Standard;
use Exception;
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
use Illuminate\Support\HtmlString;

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
            ->columns(3)
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a unique code for this control. This code will be used to identify this control in the system.')
                    ->maxLength(255)
                    ->unique(Control::class, 'code', ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) {
                        $livewire->validateOnly($component->getStatePath());
                    }),
                Forms\Components\Select::make('standard_id')
                    ->label('Standard')
                    ->searchable()
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'All controls must belong to a standard. If you dont have a standard to relate this control to, consider creating a new one first.')
                    ->required(),
                Forms\Components\Select::make('enforcement')
                    ->options(ControlEnforcementCategory::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select an enforcement category for this control. This will help determine how this control is enforced.')
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a title for this control.')
                    ->maxLength(1024),
                TinyEditor::make('description')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a description for this control. This should describe, in detail, the requirements for this this control.')
                    ->extraInputAttributes(['class' => 'filament-forms-rich-editor-unfiltered'])
                    ->columnSpanFull(),
                TinyEditor::make('discussion')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Optional: Provide any context or additional information about this control that would help someone determine how to implement it.')
                    ->columnSpanFull(),
                TinyEditor::make('test')
                    ->label('Test Plan')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Optional: How do you plan to test that this control is in place and effective?')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->description(new class implements \Illuminate\Contracts\Support\Htmlable {
                public function toHtml()
                {
                    return "<div class='fi-section-content p-6'>
                        Controls are the 'how' of security implementation - they are the specific mechanisms, policies, procedures, 
                        and tools used to enforce standards and protect assets. Controls can be technical (like firewalls or 
                        encryption), administrative (like policies or training), or physical (like security cameras or door locks). 
                        Each control should be designed to address specific risks and meet particular security requirements defined 
                        by standards. For instance, to meet a standard requiring secure data transmission, a control might specify 
                        the use of TLS 1.2 or higher for all external communications. Controls are the practical manifestation of 
                        security standards and form the backbone of an organization's security infrastructure.
                        </div>";
                }
            })
            ->emptyStateHeading('No Controls Added Yet')
            ->emptyStateDescription(new HtmlString("There are no controls to display. Try adding new controls to your existing 
            standards. You can also import standards and controls with using <a class='text-blue-500' href='" . route("filament.app.resources.bundles.index") . "'>Content Bundles</a>."))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('standard.name')
                    ->label('Standard')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('enforcement')
                    ->sortable(),
                Tables\Columns\TextColumn::make('LatestAuditEffectiveness')
                    ->label('Effectiveness')
                    ->badge()
                    ->sortable()
                    ->default(function (Control $record) {
                        return $record->getEffectiveness();
                    }),
                Tables\Columns\TextColumn::make('applicability')
                    ->label('Applicability')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('LatestAuditDate')
                    ->label('Assessed')
                    ->sortable()
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
                Tables\Filters\SelectFilter::make('applicability')
                    ->options(Applicability::class)
                    ->label('Applicability'),
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
                            ->extraAttributes(['class' => 'control-description-text'])
                            ->html(),
                        TextEntry::make('discussion')
                            ->columnSpanFull()
                            ->hidden(fn (Control $record) => ! $record->discussion)
                            ->html(),
                        TextEntry::make('test')
                            ->label('Test Plan')
                            ->columnSpanFull()
                            ->hidden(fn (Control $record) => ! $record->discussion)
                            ->html(),
                    ]),
            ]);
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "$record->code - $record->title";
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
