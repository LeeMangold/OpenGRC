<?php

namespace App\Filament\Pages\Settings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\RichEditor;

class MailTemplatesSchema
{
    public static function schema(): array
    {
        return [
            TextInput::make('mail.templates.password_reset_subject')
                ->label('Password Reset Subject')
                ->columnSpanFull(),
            RichEditor::make('mail.templates.password_reset_body')
                ->label('Password Reset Body')
                ->columnSpanFull(),
            TextInput::make('mail.templates.new_account_subject')
                ->label('New Account Subject')
                ->columnSpanFull(),
            RichEditor::make('mail.templates.new_account_body')
                ->label('New Account Body')
                ->columnSpanFull(),
            TextInput::make('mail.templates.evidence_request_subject')
                ->label('Evidence Request Subject')
                ->columnSpanFull(),
            RichEditor::make('mail.templates.evidence_request_body')
                ->label('Evidence Request Body')
                ->columnSpanFull(),
        ];
    }
} 