<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ImplementationStatus: string implements hasColor, hasLabel
{
    case FULL = 'Implemented';
    case PARTIAL = 'Partially Implemented';
    case NONE = 'Not Implemented';
    case UNKNOWN = 'Unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::FULL => 'Implemented',
            self::PARTIAL => 'Partially Implemented',
            self::NONE => 'Not Implemented',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::FULL => 'success',
            self::PARTIAL => 'warning',
            self::NONE => 'danger',
            self::UNKNOWN => 'primary',
        };
    }
}
