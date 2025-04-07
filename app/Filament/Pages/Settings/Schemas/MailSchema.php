<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class MailSchema
{
    public static function schema(): array
    {
        return [
            TextInput::make('mail.host'),
            TextInput::make('mail.port')
                ->type('number'),
            Select::make('mail.encryption')
                ->options([
                    'TLS' => 'TLS',
                    'STARTTLS' => 'STARTTLS',
                    'none' => 'None',
                ]),
            TextInput::make('mail.username'),
            TextInput::make('mail.password')
                ->password(),
            TextInput::make('mail.from')
                ->label('From Address')
                ->email()
                ->helperText('The email address to send emails from'),
        ];
    }
}
