<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\TextInput;

class GeneralSchema
{
    public static function schema(): array
    {
        return [
            TextInput::make('general.name')
                ->default('ets')
                ->minLength(2)
                ->maxLength(16)
                ->label('Application Name')
                ->helperText('The name of your application')
                ->required(),
            TextInput::make('general.url')
                ->default('http://localhost')
                ->url()
                ->label('Application URL')
                ->helperText('The URL of your application')
                ->required(),
            TextInput::make('general.repo')
                ->default('https://repo.opengrc.com')
                ->url()
                ->label('Update Repository URL')
                ->helperText('The URL of the repository to check for content updates')
                ->required(),
        ];
    }
} 