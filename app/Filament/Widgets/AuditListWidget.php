<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class AuditListWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = '2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()->with(['manager' => fn ($q) => $q->withTrashed()])->latest('updated_at')->limit(5)
            )
            ->heading(trans('widgets.audit_list.heading'))
            ->emptyStateHeading(new HtmlString(trans('widgets.audit_list.empty_heading')))
            ->emptyStateDescription(trans('widgets.audit_list.empty_description'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                TextColumn::make('title')
                    ->url(fn (Audit $audit) => route('filament.app.resources.audits.view', $audit)),
                TextColumn::make('manager_id')
                    ->label(trans('widgets.audit_list.manager'))
                    ->formatStateUsing(function ($state, Audit $audit): string {
                        if ($state === null) {
                            return 'Unassigned';
                        }
                        $manager = $audit->manager;

                        return $manager->trashed() ? $manager->name.' (Deactivated)' : $manager->name;
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->wrap(),
            ])
            ->paginated(false)
            ->headerActions([
                Action::make('create')
                    ->label(trans('widgets.audit_list.view_all'))
                    ->url(route('filament.app.resources.audits.index'))
                    ->color('primary')
                    ->size('xs'),
            ]);
    }
}
