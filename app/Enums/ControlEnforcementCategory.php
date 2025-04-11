<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ControlEnforcementCategory: string implements hasColor, hasLabel
{
    case MANDATORY = 'Mandatory';
    case ADDRESSABLE = 'Addressable';
    case OPTIONAL = 'Optional';
    case OTHER = 'Other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MANDATORY => __('enums.control_enforcement_category.mandatory'),
            self::ADDRESSABLE => __('enums.control_enforcement_category.addressable'),
            self::OPTIONAL => __('enums.control_enforcement_category.optional'),
            self::OTHER => __('enums.control_enforcement_category.other'),
        };
    }

    public function getColor(): array|string|null
    {
        return match ($this) {
            self::MANDATORY => 'danger',
            self::ADDRESSABLE => 'warning',
            self::OPTIONAL => 'primary',
            self::OTHER => 'primary'
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MANDATORY => 'fas fa-lock',
            self::ADDRESSABLE => 'fas fa-search',
            self::OPTIONAL => 'fas fa-wrench',
            self::OTHER => 'fas fa-question',
        };
    }

    public function getIconColor(): string
    {
        return match ($this) {
            self::MANDATORY => 'text-danger',
            self::ADDRESSABLE => 'text-warning',
            self::OPTIONAL => 'text-primary',
            self::OTHER => 'text-primary',
        };
    }
}
