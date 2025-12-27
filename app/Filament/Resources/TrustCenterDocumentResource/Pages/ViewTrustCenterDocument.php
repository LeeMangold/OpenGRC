<?php

namespace App\Filament\Resources\TrustCenterDocumentResource\Pages;

use App\Filament\Resources\TrustCenterDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTrustCenterDocument extends ViewRecord
{
    protected static string $resource = TrustCenterDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('download')
                ->label(__('Download'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    $disk = setting('storage.driver', 'private');
                    $storage = \Illuminate\Support\Facades\Storage::disk($disk);

                    if ($storage->exists($this->record->file_path)) {
                        return $storage->download(
                            $this->record->file_path,
                            $this->record->file_name
                        );
                    }
                }),
        ];
    }
}
