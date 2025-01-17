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
                DataRequestResponse::query()->where('requestee_id', auth()->id())->latest('updated_at')->take(5)
            )
            ->heading('My ToDo List (Top-5)')
            ->emptyStateHeading(new HtmlString("You're all caught up!"))
            ->emptyStateDescription('You have no pending ToDo items.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->columns([
                Tables\Columns\TextColumn::make('request.title')
                    ->label('Request Title')
                    ->sortable(),
                Tables\Columns\TextColumn::make('request.description')
                    ->label('Description')
                    ->wrap(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label("View All My ToDo's")
                    ->url(route('filament.app.resources.audits.index'))
                    ->color('primary')
                    ->size('xs'),
            ])
            ->paginated(false);
    }
}
