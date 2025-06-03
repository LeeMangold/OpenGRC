<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorStatus: string implements HasColor, HasLabel
{
    case PENDING = 'Pending';
    case ACCEPTED = 'Accepted';
    case REJECTED = 'Rejected';
    case EXPIRED = 'Expired';
    case TERMINATED = 'Terminated';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::EXPIRED => 'Expired',
            self::TERMINATED => 'Terminated',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
            self::EXPIRED => 'gray',
            self::TERMINATED => 'danger',
        };
    }
} 