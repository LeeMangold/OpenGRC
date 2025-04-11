<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ControlType: string implements hasColor, hasLabel
{
    case ADMINISTRATIVE = 'Administrative';
    case TECHNICAL = 'Technical';
    case PHYSICAL = 'Physical';
    case OPERATIONAL = 'Operational';
    case OTHER = 'Other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ADMINISTRATIVE => __('enums.control_type.administrative'),
            self::TECHNICAL => __('enums.control_type.technical'),
            self::PHYSICAL => __('enums.control_type.physical'),
            self::OPERATIONAL => __('enums.control_type.operational'),
            self::OTHER => __('enums.control_type.other'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ADMINISTRATIVE => 'primary',
            self::TECHNICAL => 'primary',
            self::PHYSICAL => 'primary',
            self::OPERATIONAL => 'primary',
            self::OTHER => 'primary',
        };
    }
}
