<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\FileUpload;

class ReportSchema
{
    public static function schema(): array
    {
        return [
            FileUpload::make('report.logo')
                ->label('Report Logo')
                ->helperText('The logo to display on reports. Be sure to upload a file that is at least 512px wide.')
                ->acceptedFileTypes(['image/*'])
                ->disk('public')
                ->maxFiles(1)
                ->imagePreviewHeight('150px'),
        ];
    }
} 