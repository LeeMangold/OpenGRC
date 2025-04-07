<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Crypt;

class AiSchema
{
    public static function schema(): array
    {
        return [
            Toggle::make('ai.enabled')
                ->label('Enable AI Suggestions')
                ->default(false),
            TextInput::make('ai.openai_key')
                ->label('OpenAI API Key (Optional)')
                ->password()
                ->helperText('The API key for OpenAI')
                ->default(fn ($state) => filled($state) ? Crypt::decryptString($state) : null)
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null),
        ];
    }
}
