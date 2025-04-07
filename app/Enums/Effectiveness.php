<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Effectiveness: string implements hasColor, hasLabel
{
    case EFFECTIVE = 'Effective';
    case PARTIAL = 'Partially Effective';
    case INEFFECTIVE = 'Not Effective';
    case UNKNOWN = 'Not Assessed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EFFECTIVE => 'Effective',
            self::PARTIAL => 'Partially Effective',
            self::INEFFECTIVE => 'Not Effective',
            self::UNKNOWN => 'Not Assessed',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EFFECTIVE => 'success',
            self::PARTIAL => 'warning',
            self::INEFFECTIVE => 'danger',
            self::UNKNOWN => 'primary',
        };
    }
}
