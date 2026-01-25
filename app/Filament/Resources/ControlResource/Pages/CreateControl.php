<?php

namespace App\Filament\Resources\ControlResource\Pages;

use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Resources\ControlResource;
use Filament\Resources\Pages\CreateRecord;

class CreateControl extends CreateRecord
{
    use HasTaxonomyFields;

    protected static string $resource = ControlResource::class;
}
