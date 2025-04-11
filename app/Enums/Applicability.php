<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Applicability: string implements HasColor, HasLabel
{
    case APPLICABLE = 'Applicable';
    case NOTAPPLICABLE = 'Not Applicable';
    case PARTIALLYAPPLICABLE = 'Partially Applicable';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::APPLICABLE => __('enums.applicability.applicable'),
            self::NOTAPPLICABLE => __('enums.applicability.not_applicable'),
            self::PARTIALLYAPPLICABLE => __('enums.applicability.partially_applicable'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::APPLICABLE => 'success',
            self::NOTAPPLICABLE => 'danger',
            self::PARTIALLYAPPLICABLE => 'warning',
        };
    }
}
