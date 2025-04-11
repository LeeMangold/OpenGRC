<?php

namespace App\Filament\Widgets;

use App\Models\Audit;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class AuditListWidget extends BaseWidget
{
    protected int|string|array $columnSpan = '2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Audit::query()->latest('updated_at')->take(5)
            )
            ->heading(trans('widgets.audit_list.heading'))
            ->emptyStateHeading(new HtmlString(trans('widgets.audit_list.empty_heading')))
            ->emptyStateDescription(trans('widgets.audit_list.empty_description'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->url(fn (Audit $audit) => route('filament.app.resources.audits.view', $audit)),
                Tables\Columns\TextColumn::make('manager_id')
                    ->label(trans('widgets.audit_list.manager'))
                    ->state(fn (Audit $audit) => $audit->manager->name),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->wrap(),
            ])
            ->paginated(false)
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label(trans('widgets.audit_list.view_all'))
                    ->url(route('filament.app.resources.audits.index'))
                    ->color('primary')
                    ->size('xs'),
            ]);
    }
}
