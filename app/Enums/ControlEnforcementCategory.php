<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ControlEnforcementCategory: string implements hasColor, hasLabel
{
    case PREVENTATIVE = 'Mandatory';
    case DETECTIVE = 'Addressable';
    case CORRECTIVE = 'Optional';
    case UNKNOWN = 'Other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PREVENTATIVE => 'Mandatory',
            self::DETECTIVE => 'Addressable',
            self::CORRECTIVE => 'Optional',
            self::UNKNOWN => 'Other',
        };
    }

    public function getColor(): array|string|null
    {
        return match ($this) {
            self::PREVENTATIVE => 'primary',
            self::DETECTIVE => 'primary',
            self::CORRECTIVE => 'primary',
            self::UNKNOWN => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::PREVENTATIVE => 'fas fa-lock',
            self::DETECTIVE => 'fas fa-search',
            self::CORRECTIVE => 'fas fa-wrench',
            self::UNKNOWN => 'fas fa-question',
        };
    }

    public function getIconColor(): string
    {
        return match ($this) {
            self::PREVENTATIVE => 'text-primary',
            self::DETECTIVE => 'text-primary',
            self::CORRECTIVE => 'text-primary',
            self::UNKNOWN => 'text-primary',
        };
    }
}
