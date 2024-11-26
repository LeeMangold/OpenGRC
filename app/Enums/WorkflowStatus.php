<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum WorkflowStatus: string implements hasColor, hasLabel
{
    case NOTSTARTED = 'Not Started';
    case INPROGRESS = 'In Progress';
    case COMPLETED = 'Completed';
    case UNKNOWN = 'Unknown';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NOTSTARTED => 'Not Started',
            self::INPROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::UNKNOWN => 'Unknown',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NOTSTARTED => 'danger',
            self::INPROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::UNKNOWN => 'primary',
        };
    }
}
