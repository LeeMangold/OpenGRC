<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Effectiveness: string implements HasColor, HasLabel
{
    case EFFECTIVE = 'Effective';
    case PARTIAL = 'Partially Effective';
    case INEFFECTIVE = 'Not Effective';
    case UNKNOWN = 'Not Assessed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::EFFECTIVE => __('enums.effectiveness.effective'),
            self::PARTIAL => __('enums.effectiveness.partial'),
            self::INEFFECTIVE => __('enums.effectiveness.ineffective'),
            self::UNKNOWN => __('enums.effectiveness.unknown'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::EFFECTIVE => 'success',
            self::PARTIAL => 'warning',
            self::INEFFECTIVE => 'danger',
            self::UNKNOWN => 'gray',
        };
    }
}
