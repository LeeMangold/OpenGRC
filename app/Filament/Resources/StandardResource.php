<?php

namespace App\Filament\Resources;

use App\Enums\StandardStatus;
use App\Filament\Resources\StandardResource\Pages;
use App\Filament\Resources\StandardResource\RelationManagers;
use App\Models\Standard;
use Exception;
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
use Illuminate\Support\HtmlString;

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
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Give the standard a unique ID or Code.')
                    ->unique(Standard::class, 'code', ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function ($livewire, $component) {
                        $livewire->validateOnly($component->getStatePath());
                    }),
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
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter the URL of the official standard document.'),
                RichEditor::make('description')
                    ->columnSpanFull()
                    ->maxLength(65535)
                    ->required()
                    ->hint('Describe the purpose and scope of the standard.')
                    ->placeholder('Description'),
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
                        Standards define the 'what' in security and compliance by establishing specific requirements, guidelines, 
                        or best practices that need to be followed. They serve as benchmarks against which an organization's 
                        security posture can be measured. Standards can originate from various sources, including regulatory 
                        bodies (like HIPAA or GDPR), industry frameworks (such as ISO 27001 or NIST), or internal organizational 
                        policies. Each standard typically outlines specific criteria that must be met to achieve compliance or 
                        maintain security. For example, a password standard might specify minimum length requirements, complexity 
                        rules, and expiration periods. Standards provide the foundation for controls, which then implement these 
                        requirements in practical ways.
                        </div>";
                }
            })
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
                Tables\Actions\ViewAction::make()->hiddenLabel(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('set_in_scope')
                        ->label('Set In Scope')
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading('Set Standard In Scope')
                        ->modalContent(new HtmlString('Setting a Standard as "in-scope" will allow you to track and 
                        audit to this standard. However, it will also show up in your dashboards and metrics. You can 
                        undo this action with minimal disruption.<br><br>
                        Are you sure you want to set this standard in scope?'))
                        ->modalSubmitActionLabel('Set In Scope')
                        ->hidden(
                            fn ($record) => $record->status === StandardStatus::IN_SCOPE
                        )
                        ->action(fn ($record) => $record->update(['status' => StandardStatus::IN_SCOPE]))
                    ,
                    Tables\Actions\Action::make('set_out_scope')
                        ->label('Remove from Scope')
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading('Remove from Scope')
                        ->modalContent(new HtmlString('If you remove this standard from scope, it will no longer
                        appear in your dashboards and metrics. You will also not be able to audit the standard until 
                        you set it back in-scope. No data will be modified as a result - this can be undone.<br><br>
                        Are you sure you want to set this standard in scope?'))
                        ->modalSubmitActionLabel('Remove from Scope')
                        ->hidden(
                            fn ($record) => $record->status !== StandardStatus::IN_SCOPE
                        )
                        ->action(fn ($record) => $record->update(['status' => StandardStatus::DRAFT]))
                    ,
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])->label('Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(new HtmlString('No Standards Found'))
            ->emptyStateDescription(
                new HtmlString("Try creating a new standard or adding one from an <a style='text-decoration: underline' href='"
                . route('filament.app.resources.bundles.index') .
                "'>OpenGRC Bundle</a>"));
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\AuditsRelationManager::class
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

    // This is the view page for a single Standard record
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
