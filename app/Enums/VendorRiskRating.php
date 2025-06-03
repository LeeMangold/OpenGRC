<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorRiskRating: string implements HasColor, HasLabel
{
    case VERY_LOW = 'Very Low';
    case LOW = 'Low';
    case MEDIUM = 'Medium';
    case HIGH = 'High';
    case CRITICAL = 'Critical';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VERY_LOW => 'Very Low',
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::CRITICAL => 'Critical',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VERY_LOW => 'success',
            self::LOW => 'info',
            self::MEDIUM => 'warning',
            self::HIGH => 'danger',
            self::CRITICAL => 'danger',
        };
    }
} 