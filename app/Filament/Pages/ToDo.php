<?php

namespace App\Filament\Pages;

use App\Enums\ResponseStatus;
use App\Models\DataRequestResponse;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class ToDo extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static string $view = 'filament.pages.to-do';

    protected function getTableQuery(): Builder
    {
        return DataRequestResponse::query()->where('requestee_id', auth()->id());
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('dataRequest.audit.title')->label('Audit'),
            TextColumn::make('dataRequest.details')->label('Requested Information')->html()->limit(100),
            TextColumn::make('status')->label('Status'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->label('Show Responded')
                ->multiple()
                ->options(ResponseStatus::class)
                ->default(['Pending', 'Rejected']),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('Respond')
                ->url(fn (DataRequestResponse $record): string => route('filament.app.resources.data-request-responses.edit', $record)),
        ];
    }
}
