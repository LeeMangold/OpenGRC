<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ApplicationStatus: string implements HasColor, HasLabel
{
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
    case LIMITED = 'Limited';
    case EXPIRED = 'Expired';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::LIMITED => 'Limited',
            self::EXPIRED => 'Expired',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::LIMITED => 'warning',
            self::EXPIRED => 'gray',
        };
    }
} 