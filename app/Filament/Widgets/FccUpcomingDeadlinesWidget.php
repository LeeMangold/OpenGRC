<?php

namespace App\Filament\Widgets;

use App\Models\FccDeadline;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FccUpcomingDeadlinesWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Upcoming FCC Deadlines';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FccDeadline::query()
                    ->whereIn('status', ['upcoming', 'due_soon', 'overdue'])
                    ->orderBy('due_date')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('due_date')->date('M d, Y')->label('Date')->sortable(),
                TextColumn::make('title')->weight('bold')->limit(60),
                TextColumn::make('deadline_type')->badge()
                    ->formatStateUsing(fn ($s) => str($s)->headline()),
                TextColumn::make('license.call_sign')->label('Call Sign'),
                TextColumn::make('due_date')
                    ->label('Days')
                    ->state(fn (FccDeadline $r) => max(0, (int) now()->startOfDay()->diffInDays($r->due_date->startOfDay(), false)).' days'),
                TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                    'upcoming' => 'gray', 'due_soon' => 'warning',
                    'overdue' => 'danger', 'completed' => 'success', default => 'gray',
                })->formatStateUsing(fn ($s) => str($s)->headline()),
            ])
            ->paginated(false);
    }
}
