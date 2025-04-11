<?php

namespace App\Filament\Widgets;

use App\Models\DataRequestResponse;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\HtmlString;

class ToDoListWidget extends BaseWidget
{
    protected int|string|array $columnSpan = '2';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DataRequestResponse::query()->where('requestee_id', auth()->id())->take(5)
            )
            ->heading(trans('widgets.todo.heading'))
            ->emptyStateHeading(new HtmlString(trans('widgets.todo.empty_heading')))
            ->emptyStateDescription(trans('widgets.todo.empty_description'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('dataRequest.audit.title')
                    ->label(trans('widgets.todo.audit')),
                Tables\Columns\TextColumn::make('dataRequest.details')
                    ->label(trans('widgets.todo.request_details'))
                    ->url(fn (DataRequestResponse $record) => route('filament.app.resources.data-request-responses.edit', $record))
                    ->limit(100),
                Tables\Columns\TextColumn::make('status')
                    ->label(trans('widgets.todo.status'))
                    ->badge()
                    ->wrap(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label(trans('widgets.todo.due_date')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(trans('widgets.todo.view'))
                    ->url(fn (DataRequestResponse $record): string => route('filament.app.resources.data-request-responses.edit', $record)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label(trans('widgets.todo.view_all'))
                    ->url(route('filament.app.pages.to-do'))
                    ->color('primary')
                    ->size('xs'),
            ])
            ->paginated(false);
    }

    //    protected function getEmptyState(): bool
    //    {
    //        if ( auth()->user()->openTodos()->count() === 0 ) {
    //            return true;
    //        }
    //
    //        return false;
    //    }
}
