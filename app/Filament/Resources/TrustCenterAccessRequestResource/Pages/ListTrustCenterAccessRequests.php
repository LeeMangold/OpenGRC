<?php

namespace App\Filament\Resources\TrustCenterAccessRequestResource\Pages;

use App\Enums\AccessRequestStatus;
use App\Filament\Resources\TrustCenterAccessRequestResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTrustCenterAccessRequests extends ListRecords
{
    protected static string $resource = TrustCenterAccessRequestResource::class;

    public function getTabs(): array
    {
        return [
            'pending' => Tab::make(__('Pending'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AccessRequestStatus::PENDING))
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', AccessRequestStatus::PENDING)->count())
                ->badgeColor('warning'),
            'approved' => Tab::make(__('Approved'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AccessRequestStatus::APPROVED)),
            'rejected' => Tab::make(__('Rejected'))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', AccessRequestStatus::REJECTED)),
            'all' => Tab::make(__('All')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
