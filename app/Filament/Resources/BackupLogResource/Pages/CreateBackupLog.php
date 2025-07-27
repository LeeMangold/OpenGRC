<?php

namespace App\Filament\Resources\BackupLogResource\Pages;

use App\Filament\Resources\BackupLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBackupLog extends CreateRecord
{
    protected static string $resource = BackupLogResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 