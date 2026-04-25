<?php

namespace App\Filament\Admin\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Filament\Admin\Resources\TaxonomyResource\Pages;
use App\Filament\Admin\Resources\TaxonomyResource\Pages\CreateTaxonomy;
use App\Filament\Admin\Resources\TaxonomyResource\Pages\EditTaxonomy;
use App\Filament\Admin\Resources\TaxonomyResource\Pages\ListTaxonomies;
use App\Filament\Admin\Resources\TaxonomyResource\RelationManagers\TaxonomyChildrenRelationManager;
use App\Filament\Concerns\RestoresSoftDeletedTaxonomies;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaxonomyResource extends Resource
{
    protected static ?string $model = Taxonomy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Taxonomy Types';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 30;

    /**
     * Protected system taxonomy slugs that cannot be deleted
     */
    protected static array $protectedTaxonomySlugs = [
        'scope',
        'department',
        'asset-type',
        'asset-status',
        'asset-condition',
        'compliance-status',
        'data-classification',
        'asset-category',
        'asset-location',
        'asset-supplier',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique('taxonomies', 'name', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                    ->maxLength(255)
                    ->label('Taxonomy Name')
                    ->helperText('e.g., Department, Scope, Risk Level'),
                Textarea::make('description')
                    ->maxLength(1000)
                    ->columnSpanFull()
                    ->label('Description')
                    ->helperText('Optional description of this taxonomy'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Taxonomy Name'),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->label('Slug'),
                TextColumn::make('children_count')
                    ->counts('children')
                    ->label('Terms Count')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->label('Description'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Taxonomy $record): bool => in_array($record->slug, self::$protectedTaxonomySlugs))
                    ->disabled(fn (Taxonomy $record): bool => in_array($record->slug, self::$protectedTaxonomySlugs))
                    ->action(fn (Taxonomy $record) => RestoresSoftDeletedTaxonomies::deleteTaxonomyWithChildren($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if (! in_array($record->slug, self::$protectedTaxonomySlugs)) {
                                    RestoresSoftDeletedTaxonomies::deleteTaxonomyWithChildren($record);
                                }
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TaxonomyChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxonomies::route('/'),
            'create' => CreateTaxonomy::route('/create'),
            // 'view' => Pages\ViewTaxonomy::route('/{record}'),
            'edit' => EditTaxonomy::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('parent_id'); // Only show root taxonomies
    }
}
