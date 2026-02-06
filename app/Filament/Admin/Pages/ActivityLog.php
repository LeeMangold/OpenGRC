<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected string $view = 'filament.admin.pages.activity-log';

    protected static ?int $navigationSort = 850;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    public static function getNavigationLabel(): string
    {
        return __('Activity Log');
    }

    public function getTitle(): string
    {
        return __('Activity Log');
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('View Audit Log');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Activity::query()->with(['causer' => fn ($q) => $q->withTrashed(), 'subject'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auth' => 'info',
                        'default' => 'gray',
                        default => 'primary',
                    })
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'login' => 'info',
                        'logout' => 'gray',
                        'failed_login' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-'),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->formatStateUsing(fn ($record): string => $record->causer?->displayName() ?? '-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log')
                    ->options(fn () => Activity::distinct()->pluck('log_name', 'log_name')->toArray()),
                SelectFilter::make('event')
                    ->label('Event')
                    ->options(fn () => Activity::distinct()->whereNotNull('event')->pluck('event', 'event')->toArray()),
                SelectFilter::make('subject_type')
                    ->label('Subject Type')
                    ->options(fn () => Activity::distinct()
                        ->whereNotNull('subject_type')
                        ->pluck('subject_type')
                        ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                        ->toArray()),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('60s');
    }
}
