<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum RiskStatus: string implements hasColor, hasDescription, hasLabel
{
    case NOT_ASSESSED = 'Not Assessed';
    case IN_PROGRESS = 'In Progress';
    case ASSESSED = 'Assessed';
    case CLOSED = 'Closed';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NOT_ASSESSED => 'danger',
            self::IN_PROGRESS => 'warning',
            self::ASSESSED => 'success',
            self::CLOSED => 'primary',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::NOT_ASSESSED => 'Risk has not been assessed',
            self::IN_PROGRESS => 'Risk assessment is in progress',
            self::ASSESSED => 'Risk has been assessed',
            self::CLOSED => 'Risk has been closed',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NOT_ASSESSED => 'Not Assessed',
            self::IN_PROGRESS => 'In Progress',
            self::ASSESSED => 'Assessed',
            self::CLOSED => 'Closed',
        };
    }
}
