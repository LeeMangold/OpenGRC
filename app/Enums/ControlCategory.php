<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ControlCategory: string implements HasColor, HasLabel
{
    case PREVENTIVE = 'Preventive';
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
            self::PREVENTIVE => __('enums.control_category.preventive'),
            self::DETECTIVE => __('enums.control_category.detective'),
            self::CORRECTIVE => __('enums.control_category.corrective'),
            self::DETERRENT => __('enums.control_category.deterrent'),
            self::COMPENSATING => __('enums.control_category.compensating'),
            self::RECOVERY => __('enums.control_category.recovery'),
            self::OTHER => __('enums.control_category.other'),
            self::UNKNOWN => __('enums.control_category.unknown'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PREVENTIVE => 'success',
            self::DETECTIVE => 'warning',
            self::CORRECTIVE => 'danger',
            self::DETERRENT => 'info',
            self::COMPENSATING => 'primary',
            self::RECOVERY => 'primary',
            self::OTHER => 'primary',
            self::UNKNOWN => 'primary',
        };
    }
}
