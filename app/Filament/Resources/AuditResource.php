<?php

namespace App\Filament\Resources;

use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\AuditExporter;
use App\Filament\Filters\TaxonomySelectFilter;
use App\Filament\Resources\AuditResource\Pages\CreateAudit;
use App\Filament\Resources\AuditResource\Pages\EditAudit;
use App\Filament\Resources\AuditResource\Pages\ImportIrl;
use App\Filament\Resources\AuditResource\Pages\ListAudits;
use App\Filament\Resources\AuditResource\Pages\ViewAudit;
use App\Filament\Resources\AuditResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\AuditResource\RelationManagers\AuditItemRelationManager;
use App\Filament\Resources\AuditResource\RelationManagers\DataRequestsRelationManager;
use App\Filament\Resources\AuditResource\Widgets\AuditStatsWidget;
use App\Models\Audit;
use App\Models\Control;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class AuditResource extends Resource
{
    use HasTaxonomyFields, HasWizard;

    protected static ?string $model = Audit::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('audit.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('audit.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->emptyStateHeading(__('audit.table.empty_state.heading'))
            ->emptyStateDescription(__('audit.table.empty_state.description'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('audit.table.columns.title'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('audit_type')
                    ->label(__('audit.table.columns.audit_type'))
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('audit.table.columns.status'))
                    ->sortable()
                    ->badge()
                    ->searchable(),
                TextColumn::make('manager.name')
                    ->label(__('audit.table.columns.manager'))
                    ->formatStateUsing(fn (Audit $record): string => $record->manager
                        ? ($record->manager->trashed() ? $record->manager->name.' (Deactivated)' : $record->manager->name)
                        : 'Unassigned')
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label(__('audit.table.columns.start_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label(__('audit.table.columns.end_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('department')
                    ->label('Department')
                    ->searchable(false)
                    ->formatStateUsing(function (Audit $record) {
                        // Use eager-loaded taxonomies to avoid N+1
                        if ($record->relationLoaded('taxonomies')) {
                            $department = $record->taxonomies->first(function ($tax) {
                                return $tax->parent && $tax->parent->name === 'Department';
                            });

                            return $department?->name ?? 'Not assigned';
                        }

                        return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('scope')
                    ->label('Scope')
                    ->searchable(false)
                    ->formatStateUsing(function (Audit $record) {
                        // Use eager-loaded taxonomies to avoid N+1
                        if ($record->relationLoaded('taxonomies')) {
                            $scope = $record->taxonomies->first(function ($tax) {
                                return $tax->parent && $tax->parent->name === 'Scope';
                            });

                            return $scope?->name ?? 'Not assigned';
                        }

                        return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('audit.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('audit.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('manager_id')
                    ->label('Manager')
                    ->options(User::optionsWithDeactivated())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkflowStatus::class)
                    ->searchable(),
                TaxonomySelectFilter::make('department'),
                TaxonomySelectFilter::make('scope'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(AuditExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(AuditExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function label(): string
    {
        return 'Audits';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('audit.infolist.section.title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('audit.table.columns.title')),
                        TextEntry::make('status')
                            ->label(__('audit.table.columns.status'))
                            ->badge(),
                        TextEntry::make('manager.name')
                            ->label(__('audit.table.columns.manager'))
                            ->formatStateUsing(fn (Audit $record): string => $record->manager
                                ? ($record->manager->trashed() ? $record->manager->name.' (Deactivated)' : $record->manager->name)
                                : 'Unassigned'),
                        TextEntry::make('start_date')
                            ->label(__('audit.table.columns.start_date')),
                        TextEntry::make('end_date')
                            ->label(__('audit.table.columns.end_date')),
                        TextEntry::make('taxonomies')
                            ->label('Department')
                            ->getStateUsing(function (Audit $record) {
                                return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('taxonomies')
                            ->label('Scope')
                            ->getStateUsing(function (Audit $record) {
                                return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->html(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        if (! request()->routeIs('filament.app.resources.audits.edit')) {
            return [
                AuditItemRelationManager::class,
                DataRequestsRelationManager::class,
                AttachmentsRelationManager::class,
            ];
        }

        return [];
    }

    public static function getWidgets(): array
    {
        return [
            AuditStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudits::route('/'),
            'create' => CreateAudit::route('/create'),
            'view' => ViewAudit::route('/{record}'),
            'edit' => EditAudit::route('/{record}/edit'),
            'import-irl' => ImportIrl::route('/import-irl/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['taxonomies.parent', 'manager' => fn ($q) => $q->withTrashed()]);
    }

    public static function completeAudit(Audit $audit): void
    {
        foreach ($audit->auditItems as $auditItem) {
            // If the audit item is not completed, mark it as completed
            $auditItem->update(['status' => WorkflowStatus::COMPLETED]);

            // We don't want to overwrite the effectiveness if it's already set AND we're not assessing
            $updateData = [];
            if ($auditItem->effectiveness !== Effectiveness::UNKNOWN) {

                $updateData['effectiveness'] = $auditItem->effectiveness->value;
            }
            if ($auditItem->auditable_type == Control::class) {
                $updateData['applicability'] = $auditItem->applicability->value;
            }

            if (! empty($updateData)) {
                $auditItem->auditable->update($updateData);
            }

        }

        // Save the final audit report
        $auditItems = $audit->auditItems;
        $reportTemplate = 'reports.audit';
        if ($audit->audit_type == 'implementations') {
            $reportTemplate = 'reports.implementation-report';
        }
        $filepath = "audit_reports/AuditReport-{$audit->id}.pdf";
        $pdf = Pdf::loadView($reportTemplate, ['audit' => $audit, 'auditItems' => $auditItems]);
        Storage::disk(config('filesystems.default'))->put($filepath, $pdf->output(), [
            'visibility' => 'private',
        ]);

        // Mark the audit as completed
        $audit->update(['status' => WorkflowStatus::COMPLETED]);

    }

    /**
     * @param  Audit  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    /**
     * @param  Audit  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return AuditResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Audit  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Audit $record */
        return [
            'Status' => $record->status?->getLabel() ?? 'Unknown',
            'Manager' => $record->manager?->getAttribute('name') ?? 'Unassigned',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'audit_type'];
    }
}
