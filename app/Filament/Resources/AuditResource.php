<?php

namespace App\Filament\Resources;

use App\Enums\Effectiveness;
use App\Enums\WorkflowStatus;
use App\Filament\Resources\AuditResource\Pages;
use App\Filament\Resources\AuditResource\RelationManagers;
use App\Filament\Resources\AuditResource\Widgets\AuditStatsWidget;
use App\Models\Audit;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditResource extends Resource
{
    use HasWizard;

    protected static ?string $model = Audit::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Foundations';

    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No Audits Created')
            ->emptyStateDescription('Try creating a new audit by clicking the "Create an Audit" button above to get started!')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('audit_type')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('manager.name')
                    ->label('Manager')
                    ->default('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
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
                SelectFilter::make('manager_id')
                    ->label('Manager')
                    ->options(User::query()->pluck('name', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WorkflowStatus::class)
                    ->searchable(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function label(): string
    {
        return 'Audits';
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Audit Details')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('manager.name'),
                        TextEntry::make('start_date'),
                        TextEntry::make('end_date'),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->html(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        if (! request()->routeIs('filament.app.resources.audits.edit')) {
            return [
                RelationManagers\AuditItemRelationManager::class,
                RelationManagers\DataRequestsRelationManager::class,
                RelationManagers\AttachmentsRelationManager::class,
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
            'index' => Pages\ListAudits::route('/'),
            'create' => Pages\CreateAudit::route('/create'),
            'view' => Pages\ViewAudit::route('/{record}'),
            'edit' => Pages\EditAudit::route('/{record}/edit'),
            'import-irl' => Pages\ImportIrl::route('/import-irl/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function completeAudit(Audit $audit): void
    {
        foreach ($audit->auditItems as $auditItem) {
            // If the audit item is not completed, mark it as completed
            $auditItem->update(['status' => WorkflowStatus::COMPLETED]);

            // We don't want to overwrite the effectiveness if it's already set AND we're not assessing
            if ($auditItem->effectiveness !== Effectiveness::UNKNOWN) {

                $updateData = ['effectiveness' => $auditItem->effectiveness->value];

                if ($auditItem->auditable_type == 'App\Models\Control') {
                    $updateData['applicability'] = $auditItem->applicability->value;
                }

                $auditItem->auditable->update($updateData);
            }
        }

        // Save the final audit report
        $auditItems = $audit->auditItems;
        $reportTemplate = 'reports.audit';
        if ($audit->audit_type == 'implementations') {
            $reportTemplate = 'reports.implementation-report';
        }
        $filepath = "app/private/audit_reports/AuditReport-{$audit->id}.pdf";
        $pdf = Pdf::loadView($reportTemplate, ['audit' => $audit, 'auditItems' => $auditItems]);
        $pdf->save(storage_path($filepath));

        // Mark the audit as completed
        $audit->update(['status' => WorkflowStatus::COMPLETED]);

    }
}
