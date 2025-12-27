<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;

class TrustCenterNdaSchema
{
    public static function schema(): array
    {
        return [
            Section::make(__('NDA Settings'))
                ->description(__('Configure the Non-Disclosure Agreement requirements for Trust Center access'))
                ->schema([
                    Toggle::make('trust_center.nda_required')
                        ->label(__('Require NDA Agreement'))
                        ->helperText(__('When enabled, users must agree to the NDA before accessing protected documents'))
                        ->default(true),
                    RichEditor::make('trust_center.nda_text')
                        ->label(__('NDA Text'))
                        ->helperText(__('The NDA text that will be displayed to users when requesting access'))
                        ->disableToolbarButtons([
                            'attachFiles',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
