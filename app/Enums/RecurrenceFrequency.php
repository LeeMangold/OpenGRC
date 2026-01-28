<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum RecurrenceFrequency: string implements HasColor, HasIcon, HasLabel
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DAILY => __('Daily'),
            self::WEEKLY => __('Weekly'),
            self::MONTHLY => __('Monthly'),
            self::QUARTERLY => __('Quarterly'),
            self::YEARLY => __('Yearly'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DAILY => 'danger',
            self::WEEKLY => 'warning',
            self::MONTHLY => 'info',
            self::QUARTERLY => 'primary',
            self::YEARLY => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DAILY => 'heroicon-o-sun',
            self::WEEKLY => 'heroicon-o-calendar-days',
            self::MONTHLY => 'heroicon-o-calendar',
            self::QUARTERLY => 'heroicon-o-clock',
            self::YEARLY => 'heroicon-o-calendar-date-range',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DAILY => __('Generate a new checklist every day'),
            self::WEEKLY => __('Generate a new checklist every week'),
            self::MONTHLY => __('Generate a new checklist every month'),
            self::QUARTERLY => __('Generate a new checklist every quarter'),
            self::YEARLY => __('Generate a new checklist every year'),
        };
    }
}
