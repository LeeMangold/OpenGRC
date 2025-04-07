<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\TextInput;

class SecuritySchema
{
    public static function schema(): array
    {
        return [
            TextInput::make('security.session_timeout')
                ->label('Session Timeout (minutes)')
                ->numeric()
                ->default(15)
                ->minValue(1)
                ->maxValue(1440)
                ->required()
                ->helperText('Number of minutes before an inactive session expires. Default: 15 minutes'),
        ];
    }
}
