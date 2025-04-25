<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\FileUpload;
use Storage;

class ReportSchema
{
    public static function schema(): array
    {
        return [
            FileUpload::make('report.logo')
                ->label('Custom Report Logo (Optional)')
                ->helperText('The logo to display on reports. Be sure to upload a file that is at least 512px wide.')
                ->acceptedFileTypes(['image/*'])
                ->directory('report-assets')
                ->image()
                ->disk(fn () => config('filesystems.default'))
                ->visibility('private')
                ->maxFiles(1)
                ->imagePreviewHeight('300px')
                ->deleteUploadedFileUsing(function ($state) {
                    if ($state) {
                        Storage::disk(config('filesystems.default'))->delete($state);
                    }
                }),
        ];
    }
}