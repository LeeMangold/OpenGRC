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
            ->heading('My ToDo List (Top-5)')
            ->emptyStateHeading(new HtmlString("You're all caught up!"))
            ->emptyStateDescription('You have no pending ToDo items.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('dataRequest.audit.title')
                    ->label('Audit'),
                Tables\Columns\TextColumn::make('dataRequest.details')
                    ->label('Request Details')
                    ->url(fn (DataRequestResponse $record) => route('filament.app.resources.data-request-responses.edit', $record))
                    ->limit(100),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->wrap(),
                Tables\Columns\TextColumn::make('due_at')
                    ->label('Due Date'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->url(fn (DataRequestResponse $record): string => route('filament.app.resources.data-request-responses.edit', $record)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label("View All My ToDo's")
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
