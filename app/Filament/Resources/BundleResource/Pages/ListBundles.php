<?php

namespace App\Filament\Resources\BundleResource\Pages;

use App\Filament\Resources\BundleResource;
use Filament\Resources\Pages\ListRecords;

class ListBundles extends ListRecords
{
    protected static string $resource = BundleResource::class;

    protected static ?string $title = 'Content Bundles';

    protected function getHeaderWidgets(): array
    {
        return [
            BundleResource\Widgets\BundleHeader::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
