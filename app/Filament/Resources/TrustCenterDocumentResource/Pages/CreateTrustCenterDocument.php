<?php

namespace App\Filament\Resources\TrustCenterDocumentResource\Pages;

use App\Filament\Resources\TrustCenterDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrustCenterDocument extends CreateRecord
{
    protected static string $resource = TrustCenterDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uploaded_by'] = auth()->id();

        // Get file size and mime type from the uploaded file
        if (isset($data['file_path']) && is_string($data['file_path'])) {
            $disk = setting('storage.driver', 'private');
            $storage = \Illuminate\Support\Facades\Storage::disk($disk);

            if ($storage->exists($data['file_path'])) {
                $data['file_size'] = $storage->size($data['file_path']);
                $data['mime_type'] = $storage->mimeType($data['file_path']);
            }
        }

        return $data;
    }
}
