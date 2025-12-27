<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TrustLevel: string implements HasColor, HasIcon, HasLabel
{
    case PUBLIC = 'public';
    case PROTECTED = 'protected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PROTECTED => 'Protected',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PUBLIC => 'success',
            self::PROTECTED => 'warning',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PUBLIC => 'heroicon-o-globe-alt',
            self::PROTECTED => 'heroicon-o-lock-closed',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PUBLIC => 'Available to anyone without authentication',
            self::PROTECTED => 'Requires access request and approval',
        };
    }
}
