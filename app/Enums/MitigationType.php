<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MitigationType: string implements hasColor, hasLabel
{
    case OPEN = 'Open';
    case AVOID = 'Avoid';
    case MITIGATE = 'Mitigate';
    case TRANSFER = 'Transfer';
    case ACCEPT = 'Accept';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::AVOID => 'Avoid',
            self::MITIGATE => 'Mitigate',
            self::TRANSFER => 'Transfer',
            self::ACCEPT => 'Accept',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'danger',
            self::AVOID => 'success',
            self::MITIGATE => 'warning',
            self::TRANSFER => 'danger',
            self::ACCEPT => 'primary',
        };
    }
}
