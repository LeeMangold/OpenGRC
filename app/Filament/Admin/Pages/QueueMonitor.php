<?php

namespace App\Filament\Admin\Pages;

use App\Models\QueueJob;
use Filament\Pages\Page;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class QueueMonitor extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static string $view = 'filament.admin.pages.queue-monitor';

    protected static ?string $navigationLabel = 'Queue Monitor';

    protected static ?string $title = 'Queue Monitor';

    protected static ?int $navigationSort = 900;

    protected static ?string $navigationGroup = 'System';

    public function table(Table $table): Table
    {
        return $table
            ->query(QueueJob::query())
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('payload')
                    ->label('Job')
                    ->formatStateUsing(function ($state) {
                        $payload = json_decode($state, true);

                        return $payload['displayName'] ?? 'Unknown';
                    })
                    ->wrap()
                    ->searchable(),
                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('reserved_at')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return $state ? 'Processing' : 'Pending';
                    })
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->formatStateUsing(fn ($state) => $state ? date('Y-m-d H:i:s', $state) : '-')
                    ->sortable(),
            ])
            ->actions([
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Job')
                    ->modalDescription('Are you sure you want to delete this job from the queue? This action cannot be undone.'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Jobs')
                        ->modalDescription('Are you sure you want to delete the selected jobs from the queue? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s')
            ->emptyStateHeading('No jobs in queue')
            ->emptyStateDescription('The queue is empty. Jobs will appear here when they are dispatched.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public function getQueueStats(): array
    {
        $pending = DB::table('jobs')->whereNull('reserved_at')->count();
        $processing = DB::table('jobs')->whereNotNull('reserved_at')->count();
        $failed = DB::table('failed_jobs')->count();

        return [
            'pending' => $pending,
            'processing' => $processing,
            'failed' => $failed,
            'total' => $pending + $processing,
        ];
    }

    public function getFailedJobs(): array
    {
        return DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);

                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'job' => $payload['displayName'] ?? 'Unknown',
                    'failed_at' => $job->failed_at,
                    'exception' => \Illuminate\Support\Str::limit($job->exception, 200),
                ];
            })
            ->toArray();
    }
}
