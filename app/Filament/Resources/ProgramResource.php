<?php

namespace App\Filament\Resources;

use App\Filament\Columns\TaxonomyColumn;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\ProgramExporter;
use App\Filament\Filters\TaxonomySelectFilter;
use App\Filament\Resources\ProgramResource\Pages\CreateProgram;
use App\Filament\Resources\ProgramResource\Pages\EditProgram;
use App\Filament\Resources\ProgramResource\Pages\ListPrograms;
use App\Filament\Resources\ProgramResource\Pages\ProgramPage;
use App\Filament\Resources\ProgramResource\RelationManagers\AuditsRelationManager;
use App\Filament\Resources\ProgramResource\RelationManagers\ControlsRelationManager;
use App\Filament\Resources\ProgramResource\RelationManagers\RisksRelationManager;
use App\Filament\Resources\ProgramResource\RelationManagers\StandardsRelationManager;
use App\Models\Program;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Program::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    public static function getNavigationLabel(): string
    {
        return __('navigation.resources.program');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.foundations');
    }

    public static function getModelLabel(): string
    {
        return __('programs.labels.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('programs.labels.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('programs.form.name'))
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                RichEditor::make('description')
                    ->label(__('programs.form.description'))
                    ->fileAttachmentsDisk(setting('storage.driver', 'private'))
                    ->fileAttachmentsVisibility('private')
                    ->fileAttachmentsDirectory('ssp-uploads')
                    ->columnSpanFull(),
                Select::make('program_manager_id')
                    ->label(__('programs.form.program_manager'))
                    ->relationship('programManager', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('scope_status')
                    ->label(__('programs.form.scope_status'))
                    ->options([
                        'In Scope' => __('programs.scope_status.in_scope'),
                        'Out of Scope' => __('programs.scope_status.out_of_scope'),
                        'Pending Review' => __('programs.scope_status.pending_review'),
                    ])
                    ->required(),
                self::taxonomySelect('Department', 'department')
                    ->nullable()
                    ->columnSpan(1),
                self::taxonomySelect('Scope', 'scope')
                    ->nullable()
                    ->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->recordUrl(fn (Program $record): string => ProgramPage::getUrl(['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('programs.table.name'))
                    ->searchable(),
                TextColumn::make('programManager.name')
                    ->label(__('programs.table.program_manager'))
                    ->searchable(),
                TextColumn::make('last_audit_date')
                    ->label(__('programs.table.last_audit_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('scope_status')
                    ->label(__('programs.table.scope_status'))
                    ->searchable(),
                TaxonomyColumn::make('department')
                    ->toggleable(),
                TaxonomyColumn::make('scope')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('programs.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('programs.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TaxonomySelectFilter::make('department'),
                TaxonomySelectFilter::make('scope'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ProgramExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                // Tables\Actions\EditAction::make(),
                ViewAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ProgramExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StandardsRelationManager::class,
            ControlsRelationManager::class,
            RisksRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'view' => ProgramPage::route('/{record}'),
            'edit' => EditProgram::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['taxonomies', 'programManager']);
    }
}
