<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Applicability: string implements hasColor, hasLabel
{
    case APPLICABLE = 'Applicable';
    case NOTAPPLICABLE = 'Not Applicable';
    case UNKNOWN = 'Unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::APPLICABLE => 'Applicable',
            self::NOTAPPLICABLE => 'Not Applicable',
            self::UNKNOWN => 'Not Assessed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::APPLICABLE => 'success',
            self::NOTAPPLICABLE => 'primary',
            self::UNKNOWN => 'warning',
        };
    }
}
