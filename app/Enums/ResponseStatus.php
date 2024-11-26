<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum ResponseStatus: string implements hasColor, hasDescription, hasLabel
{
    case PENDING = 'Pending';
    case RESPONDED = 'Responded';
    case REJECTED = 'Rejected';
    case ACCEPTED = 'Accepted';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::RESPONDED => 'Responded',
            self::REJECTED => 'Rejected',
            self::ACCEPTED => 'Accepted',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'primary',
            self::RESPONDED => 'warning',
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::PENDING => 'Not yet responded to.',
            self::RESPONDED => 'Request has been responded to.',
            self::ACCEPTED => 'Accepted response.',
            self::REJECTED => 'Rejected response.',
        };
    }
}
