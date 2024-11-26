<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ControlCategory: string implements hasColor, hasLabel
{
    case PREVENTATIVE = 'Preventative';
    case DETECTIVE = 'Detective';
    case CORRECTIVE = 'Corrective';
    case DETERRENT = 'Deterrent';
    case COMPENSATING = 'Compensating';
    case RECOVERY = 'Recovery';
    case OTHER = 'Other';
    case UNKNOWN = 'Unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PREVENTATIVE => 'Preventative',
            self::DETECTIVE => 'Detective',
            self::CORRECTIVE => 'Corrective',
            self::DETERRENT => 'Deterrent',
            self::COMPENSATING => 'Compensating',
            self::RECOVERY => 'Recovery',
            self::OTHER => 'Other',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PREVENTATIVE => 'primary',
            self::DETECTIVE => 'primary',
            self::CORRECTIVE => 'primary',
            self::DETERRENT => 'primary',
            self::COMPENSATING => 'primary',
            self::RECOVERY => 'primary',
            self::OTHER => 'primary',
            self::UNKNOWN => 'primary',
        };
    }
}
